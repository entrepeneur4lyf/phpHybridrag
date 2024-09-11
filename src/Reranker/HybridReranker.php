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

/**
 * Class HybridReranker
 *
 * This class implements a hybrid reranking approach combining BM25 and semantic similarity.
 */
class HybridReranker implements RerankerInterface
{
    private const CACHE_TTL = 3600; // 1 hour
    private float $averageDocumentLength;
    private array $inverseDocumentFrequency;
    private WordTokenizer $tokenizer;
    private English $stopWords;

    /**
     * HybridReranker constructor.
     *
     * @param EmbeddingInterface $embedding The embedding interface
     * @param VectorDatabaseInterface $vectorDB The vector database interface
     * @param CacheInterface $cache The cache interface
     * @param Logger $logger The logger instance
     * @param float $bm25Weight The weight for BM25 scoring (default: 0.5)
     * @param float $semanticWeight The weight for semantic scoring (default: 0.5)
     */
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

    /**
     * Rerank the given results based on the query.
     *
     * @param string $query The query string
     * @param array $results The initial results to rerank
     * @param int $topK The number of top results to return
     * @return array The reranked results
     * @throws HybridRAGException If reranking fails
     */
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

    /**
     * Calculate corpus statistics for BM25 scoring.
     *
     * @throws HybridRAGException If calculation fails
     */
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

            $this->averageDocumentLength = $totalDocuments > 0 ? $totalLength / $totalDocuments : 1; // Avoid division by zero

            $this->inverseDocumentFrequency = [];
            foreach ($termDocumentFrequency as $term => $frequency) {
                $this->inverseDocumentFrequency[$term] = $frequency > 0 ? log(($totalDocuments - $frequency + 0.5) / ($frequency + 0.5)) : 0;
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

    /**
     * Calculate BM25 score for a document given query terms.
     *
     * @param array $queryTerms The tokenized query terms
     * @param string $document The document content
     * @return float The BM25 score
     */
    private function calculateBM25Score(array $queryTerms, string $document): float
    {
        $k1 = 1.2;
        $b = 0.75;
        $documentTerms = $this->tokenize($document);
        $documentLength = count($documentTerms);

        if ($documentLength === 0) {
            return 0.0; // Avoid division by zero
        }

        $score = 0;
        foreach ($queryTerms as $term) {
            $termFrequency = $this->getTermFrequency($term, $documentTerms);
            $inverseDocumentFrequency = $this->getInverseDocumentFrequency($term);
            $denominator = $termFrequency + $k1 * (1 - $b + $b * ($documentLength / $this->averageDocumentLength));
            if ($denominator != 0) {
                $score += $inverseDocumentFrequency * (($termFrequency * ($k1 + 1)) / $denominator);
            }
        }

        return $score;
    }

    /**
     * Calculate semantic similarity between query and document.
     *
     * @param array $queryEmbedding The query embedding
     * @param string $document The document content
     * @return float The semantic similarity score
     * @throws HybridRAGException If calculation fails
     */
    private function calculateSemanticSimilarity(array $queryEmbedding, string $document): float
    {
        try {
            $documentEmbedding = $this->embedding->embed($document) ?? [];
            return $this->cosineSimilarity($queryEmbedding, $documentEmbedding);
        } catch (\Exception $e) {
            $this->logger->error("Failed to calculate semantic similarity", ['error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to calculate semantic similarity: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Tokenize and filter stop words from the given text.
     *
     * @param string $text The text to tokenize
     * @return array The tokenized and filtered words
     */
    private function tokenize(string $text): array
    {
        $tokens = $this->tokenizer->tokenize(strtolower($text));
        return array_filter($tokens, function($token) {
            return !$this->stopWords->isStopWord($token);
        });
    }

    /**
     * Get the frequency of a term in the given terms.
     *
     * @param string $term The term to count
     * @param array $terms The list of terms
     * @return int The frequency of the term
     */
    private function getTermFrequency(string $term, array $terms): int
    {
        return array_count_values($terms)[$term] ?? 0;
    }

    /**
     * Get the inverse document frequency for a term.
     *
     * @param string $term The term to look up
     * @return float The inverse document frequency
     */
    private function getInverseDocumentFrequency(string $term): float
    {
        return $this->inverseDocumentFrequency[$term] ?? 0;
    }

    /**
     * Calculate the cosine similarity between two vectors.
     *
     * @param array $a The first vector
     * @param array $b The second vector
     * @return float The cosine similarity
     */
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

        return ($magnitudeA * $magnitudeB) != 0 ? $dotProduct / ($magnitudeA * $magnitudeB) : 0.0;
    }

    /**
     * Generate a cache key for the given query and results.
     *
     * @param string $query The query string
     * @param array $results The results array
     * @param int $topK The number of top results
     * @return string The generated cache key
     */
    private function getCacheKey(string $query, array $results, int $topK): string
    {
        $resultsHash = md5(json_encode($results));
        return "reranker_" . md5($query . $resultsHash . $topK);
    }
}
