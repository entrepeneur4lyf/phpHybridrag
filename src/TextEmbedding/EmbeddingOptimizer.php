<?php

declare(strict_types=1);

namespace HybridRAG\TextEmbedding;

use Phpml\FeatureSelection\SelectKBest;
use Phpml\FeatureSelection\SelectFromModel;
use Phpml\Regression\LassoRegression;

/**
 * Class EmbeddingOptimizer
 *
 * This class provides methods to optimize embeddings using feature selection techniques.
 */
class EmbeddingOptimizer
{
    private SelectKBest $selectKBest;
    private SelectFromModel $selectFromModel;

    /**
     * EmbeddingOptimizer constructor.
     *
     * @param int $k The number of top features to select
     */
    public function __construct(int $k = 100)
    {
        $this->selectKBest = new SelectKBest($k);
        $this->selectFromModel = new SelectFromModel(new LassoRegression(), 0.1);
    }

    /**
     * Optimize the given embeddings using feature selection techniques.
     *
     * @param array $embeddings The input embeddings to optimize
     * @param array $labels The corresponding labels for the embeddings
     * @return array The optimized embeddings
     */
    public function optimizeEmbeddings(array $embeddings, array $labels): array
    {
        $optimizedEmbeddings = $this->selectKBest->fit($embeddings, $labels)->transform($embeddings);
        return $this->selectFromModel->fit($optimizedEmbeddings, $labels)->transform($optimizedEmbeddings);
    }
}
