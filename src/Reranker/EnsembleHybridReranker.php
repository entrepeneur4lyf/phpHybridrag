<?php

declare(strict_types=1);

namespace HybridRAG\Reranker;

use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\VectorDatabase\VectorDatabaseInterface;
use Psr\SimpleCache\CacheInterface;
use Phpml\Ensemble\RandomForest;
use Phpml\Classification\DecisionTree;

/**
 * Class EnsembleHybridReranker
 *
 * This class extends the HybridReranker to implement an ensemble reranking approach using Random Forest.
 */
class EnsembleHybridReranker extends HybridReranker
{
    private RandomForest $randomForest;

    /**
     * EnsembleHybridReranker constructor.
     *
     * @param EmbeddingInterface $embedding The embedding interface
     * @param VectorDatabaseInterface $vectorDB The vector database interface
     * @param CacheInterface $cache The cache interface
     * @param float $bm25Weight The weight for BM25 score
     * @param float $semanticWeight The weight for semantic score
     */
    public function __construct(
        EmbeddingInterface $embedding,
        VectorDatabaseInterface $vectorDB,
        CacheInterface $cache,
        float $bm25Weight = 0.5,
        float $semanticWeight = 0.5
    ) {
        parent::__construct($embedding, $vectorDB, $cache, $bm25Weight, $semanticWeight);
        $this->randomForest = new RandomForest();
    }

    /**
     * Rerank the results using the ensemble approach.
     *
     * @param string $query The query string
     * @param array $results The initial results to rerank
     * @param int $topK The number of top results to return
     * @return array The reranked results
     */
    public function rerank(string $query, array $results, int $topK): array
    {
        $rerankedResults = parent::rerank($query, $results, $topK);
        
        $features = $this->extractFeatures($query, $rerankedResults);
        $predictions = $this->randomForest->predict($features);
        
        array_multisort($predictions, SORT_DESC, $rerankedResults);
        
        return array_slice($rerankedResults, 0, $topK);
    }

    /**
     * Extract features from the query and results for the Random Forest model.
     *
     * @param string $query The query string
     * @param array $results The results to extract features from
     * @return array The extracted features
     */
    private function extractFeatures(string $query, array $results): array
    {
        $features = [];
        foreach ($results as $result) {
            $features[] = [
                $result['bm25_score'],
                $result['semantic_score'],
                $result['combined_score'],
                strlen($result['content']),
                $this->calculateQueryOverlap($query, $result['content'])
            ];
        }
        return $features;
    }

    /**
     * Calculate the overlap between the query and content tokens.
     *
     * @param string $query The query string
     * @param string $content The content to compare with
     * @return float The overlap score
     */
    private function calculateQueryOverlap(string $query, string $content): float
    {
        $queryTokens = $this->tokenize($query);
        $contentTokens = $this->tokenize($content);
        $overlap = array_intersect($queryTokens, $contentTokens);
        return count($overlap) / count($queryTokens);
    }

    /**
     * Tokenize the given text.
     *
     * @param string $text The text to tokenize
     * @return array An array of tokens
     */
    private function tokenize(string $text): array
    {
        // Implement tokenization logic here
        return explode(' ', strtolower($text));
    }
}
