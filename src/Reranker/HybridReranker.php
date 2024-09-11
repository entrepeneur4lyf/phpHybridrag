<?php

declare(strict_types=1);

namespace HybridRAG\Reranker;

use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\VectorDatabase\VectorDatabaseInterface;
use HybridRAG\Logging\Logger;
use HybridRAG\Exception\HybridRAGException;
use Psr\SimpleCache\CacheInterface;
use Phpml\Tokenization\WordTokenizer;
use Phpml\FeatureExtraction\StopWords\English;

class HybridReranker implements RerankerInterface
{
    private const CACHE_TTL = 3600; // 1 hour
    private float $averageDocumentLength;
    private array $inverseDocumentFrequency;
    private WordTokenizer $tokenizer;
    private English $stopWords;

    public function __construct(
        private EmbeddingInterface $embedding,
        private VectorDatabaseInterface $vectorDB,
        private CacheInterface $cache,
        private Logger $logger,
        private float $bm25Weight = 0.5,
        private float $semanticWeight = 0.5
    ) {
        $this->calculateCorpusStatistics();
        $this->tokenizer = new WordTokenizer();
        $this->stopWords = new English();
    }

    public function rerank(string $query, array $results, int $topK): array
    {
        try {
            $this->logger->info("Reranking results", ['query' => $query, 'topK' => $topK]);
            $cacheKey = $this->getCacheKey($query, $results, $topK);
            if ($this->cache->has($cacheKey)) {
                $this->logger->info("Returning cached reranked results", ['query' => $query]);
                return $this->cache->get($cacheKey);
            }

            $queryTerms = $this->tokenize($query);
            $queryEmbedding = $this->embedding->embed($query);

            foreach ($results as &$result) {
                $result['bm25_score'] = $this->calculateBM25Score($queryTerms, $result['content']);
                $result['semantic_score'] = $this->calculateSemanticSimilarity($queryEmbedding, $result['content']);
                $result['combined_score'] = ($this->bm25Weight * $result['bm25_score']) + ($this->semanticWeight * $result['semantic_score']);
            }

            usort($results, function ($a, $b) {
                return $b['combined_score'] <=> $a['combined_score'];
            });

            $rerankedResults = array_slice($results, 0, $topK);

            $this->cache->set($cacheKey, $rerankedResults, self::CACHE_TTL);

            $this->logger->info("Results reranked successfully", ['query' => $query, 'rerankedCount' => count($rerankedResults)]);
            return $rerankedResults;
        } catch (\Exception $e) {
            $this->logger->error("Failed to rerank results", ['query' => $query, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to rerank results: {$e->getMessage()}", 0, $e);
        }
    }

    private function calculateCorpusStatistics(): void
    {
        try {
            $this->logger->info("Calculating corpus statistics");
            $cacheKey = 'corpus_statistics';
            if ($this->cache->has($cacheKey)) {
                $statistics = $this->cache->get($cacheKey);
                $this->averageDocumentLength = $statistics['averageDocumentLength'];
                $this->inverseDocumentFrequency = $statistics['inverseDocumentFrequency'];
                $this->logger->info("Corpus statistics loaded from cache");
                return;
            }

            $totalDocuments = 0;
            $totalLength = 0;
            $termDocumentFrequency = [];

            $allDocuments = $this->vectorDB->getAllDocuments();

            foreach ($allDocuments as $document) {
                $totalDocuments++;
                $terms = $this->tokenize($document['content']);
                $totalLength += count($terms);

                $uniqueTerms = array_unique($terms);
                foreach ($uniqueTerms as $term) {
                    $termDocumentFrequency[$term] = ($termDocumentFrequency[$term] ?? 0) + 1;
                }
            }

            $this->averageDocumentLength = $totalLength / $totalDocuments;

            $this->inverseDocumentFrequency = [];
            foreach ($termDocumentFrequency as $term => $frequency) {
                $this->inverseDocumentFrequency[$term] = log(($totalDocuments - $frequency + 0.5) / ($frequency + 0.5));
            }

            $this->cache->set($cacheKey, [
                'averageDocumentLength' => $this->averageDocumentLength,
                'inverseDocumentFrequency' => $this->inverseDocumentFrequency,
            ], 24 * 3600); // Cache for 24 hours

            $this->logger->info("Corpus statistics calculated and cached");
        } catch (\Exception $e) {
            $this->logger->error("Failed to calculate corpus statistics", ['error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to calculate corpus statistics: {$e->getMessage()}", 0, $e);
        }
    }

    private function calculateBM25Score(array $queryTerms, string $document): float
    {
        $k1 = 1.2;
        $b = 0.75;
        $documentTerms = $this->tokenize($document);
        $documentLength = count($documentTerms);

        $score = 0;
        foreach ($queryTerms as $term) {
            $termFrequency = $this->getTermFrequency($term, $documentTerms);
            $inverseDocumentFrequency = $this->getInverseDocumentFrequency($term);
            $score += $inverseDocumentFrequency * (($termFrequency * ($k1 + 1)) / ($termFrequency + $k1 * (1 - $b + $b * ($documentLength / $this->averageDocumentLength))));
        }

        return $score;
    }

    private function calculateSemanticSimilarity(array $queryEmbedding, string $document): float
    {
        try {
            $documentEmbedding = $this->embedding->embed($document);
            return $this->cosineSimilarity($queryEmbedding, $documentEmbedding);
        } catch (\Exception $e) {
            $this->logger->error("Failed to calculate semantic similarity", ['error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to calculate semantic similarity: {$e->getMessage()}", 0, $e);
        }
    }

    private function tokenize(string $text): array
    {
        $tokens = $this->tokenizer->tokenize(strtolower($text));
        return array_filter($tokens, function($token) {
            return !$this->stopWords->isStopWord($token);
        });
    }

    private function getTermFrequency(string $term, array $terms): int
    {
        return array_count_values($terms)[$term] ?? 0;
    }

    private function getInverseDocumentFrequency(string $term): float
    {
        return $this->inverseDocumentFrequency[$term] ?? 0;
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        foreach ($a as $i => $valueA) {
            $dotProduct += $valueA * $b[$i];
            $magnitudeA += $valueA * $valueA;
            $magnitudeB += $b[$i] * $b[$i];
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }

    private function getCacheKey(string $query, array $results, int $topK): string
    {
        $resultsHash = md5(json_encode($results));
        return "reranker_" . md5($query . $resultsHash . $topK);
    }
}