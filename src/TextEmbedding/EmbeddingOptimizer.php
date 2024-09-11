<?php

declare(strict_types=1);

namespace HybridRAG\TextEmbedding;

use Phpml\FeatureSelection\SelectKBest;
use Phpml\FeatureSelection\SelectFromModel;
use Phpml\Regression\LassoRegression;

class EmbeddingOptimizer
{
    private SelectKBest $selectKBest;
    private SelectFromModel $selectFromModel;

    public function __construct(int $k = 100)
    {
        $this->selectKBest = new SelectKBest($k);
        $this->selectFromModel = new SelectFromModel(new LassoRegression(), 0.1);
    }

    public function optimizeEmbeddings(array $embeddings, array $labels): array
    {
        $optimizedEmbeddings = $this->selectKBest->fit($embeddings, $labels)->transform($embeddings);
        return $this->selectFromModel->fit($optimizedEmbeddings, $labels)->transform($optimizedEmbeddings);
    }
}