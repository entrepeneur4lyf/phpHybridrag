<?php

declare(strict_types=1);

namespace HybridRAG\Reranker;

use Phpml\Ensemble\RandomForest;
use Phpml\Classification\DecisionTree;

/**
 * Class EnsembleReranker
 *
 * This class implements an ensemble reranking approach using multiple rerankers and a Random Forest model.
 */
class EnsembleReranker implements RerankerInterface
{
    private array $rerankers;
    private RandomForest $ensemble;

    /**
     * EnsembleReranker constructor.
     *
     * @param array $rerankers An array of reranker instances
     */
    public function __construct(array $rerankers)
    {
        $this->rerankers = $rerankers;
        $this->ensemble = new RandomForest();
    }

    /**
     * Rerank the given results using the ensemble of rerankers and a Random Forest model.
     *
     * @param string $query The query string
     * @param array $results The initial results to rerank
     * @param int $topK The number of top results to return
     * @return array The reranked results
     */
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
