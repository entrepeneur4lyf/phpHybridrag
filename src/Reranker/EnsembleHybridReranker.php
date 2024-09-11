<?php

declare(strict_types=1);

namespace HybridRAG\Reranker;

use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\VectorDatabase\VectorDatabaseInterface;
use Psr\SimpleCache\CacheInterface;
use Phpml\Ensemble\RandomForest;
use Phpml\Classification\DecisionTree;

class EnsembleHybridReranker extends HybridReranker
{
    private RandomForest $randomForest;

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

    public function rerank(string $query, array $results, int $topK): array
    {
        $rerankedResults = parent::rerank($query, $results, $topK);
        
        $features = $this->extractFeatures($query, $rerankedResults);
        $predictions = $this->randomForest->predict($features);
        
        array_multisort($predictions, SORT_DESC, $rerankedResults);
        
        return array_slice($rerankedResults, 0, $topK);
    }

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

    private function calculateQueryOverlap(string $query, string $content): float
    {
        $queryTokens = $this->tokenize($query);
        $contentTokens = $this->tokenize($content);
        $overlap = array_intersect($queryTokens, $contentTokens);
        return count($overlap) / count($queryTokens);
    }
}