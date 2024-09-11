<?php

declare(strict_types=1);

namespace HybridRAG\Visualization;

use Phpml\Preprocessing\Normalizer;
use Phpml\Math\Matrix;
use Phpml\Math\LinearAlgebra\EigenvalueDecomposition;

/**
 * Class EmbeddingVisualizer
 *
 * This class provides functionality to visualize embeddings by reducing their dimensionality.
 */
class EmbeddingVisualizer
{
    private Normalizer $normalizer;

    /**
     * EmbeddingVisualizer constructor.
     */
    public function __construct()
    {
        $this->normalizer = new Normalizer();
    }

    /**
     * Reduce the dimensionality of the given embeddings.
     *
     * @param array $embeddings The input embeddings to reduce
     * @param int $dimensions The number of dimensions to reduce to (default: 2)
     * @return array The reduced embeddings
     */
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
