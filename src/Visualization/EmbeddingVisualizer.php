<?php

declare(strict_types=1);

namespace HybridRAG\Visualization;

use Phpml\Preprocessing\Normalizer;
use Phpml\Math\Matrix;
use Phpml\Math\LinearAlgebra\EigenvalueDecomposition;

class EmbeddingVisualizer
{
    private Normalizer $normalizer;

    public function __construct()
    {
        $this->normalizer = new Normalizer();
    }

    public function reduceDimensionality(array $embeddings, int $dimensions = 2): array
    {
        $normalizedEmbeddings = $this->normalizer->transform($embeddings);
        $matrix = new Matrix($normalizedEmbeddings);
        $covariance = $matrix->transpose()->multiply($matrix)->divideByScalar(count($embeddings));
        
        $eigenDecomposition = new EigenvalueDecomposition($covariance->toArray());
        $eigenvalues = $eigenDecomposition->getRealEigenvalues();
        $eigenvectors = $eigenDecomposition->getEigenvectors();
        
        arsort($eigenvalues);
        $topEigenvectors = array_slice($eigenvectors, 0, $dimensions, true);
        
        $projectionMatrix = new Matrix($topEigenvectors);
        return $matrix->multiply($projectionMatrix->transpose())->toArray();
    }
}