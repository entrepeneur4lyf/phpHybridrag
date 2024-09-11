<?php

declare(strict_types=1);

namespace HybridRAG\Reranker;

use Phpml\Ensemble\RandomForest;
use Phpml\Classification\DecisionTree;

class EnsembleReranker implements RerankerInterface
{
    private array $rerankers;
    private RandomForest $ensemble;

    public function __construct(array $rerankers)
    {
        $this->rerankers = $rerankers;
        $this->ensemble = new RandomForest();
    }

    public function rerank(string $query, array $results, int $topK): array
    {
        $features = [];
        $labels = range(0, count($results) - 1);

        foreach ($this->rerankers as $reranker) {
            $rerankedResults = $reranker->rerank($query, $results, count($results));
            $scores = array_column($rerankedResults, 'score');
            $features[] = $scores;
        }

        $features = array_map(null, ...$features);
        $this->ensemble->train($features, $labels);

        $predictions = $this->ensemble->predict($features);
        array_multisort($predictions, SORT_DESC, $results);

        return array_slice($results, 0, $topK);
    }
}