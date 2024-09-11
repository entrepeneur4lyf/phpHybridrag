<?php

declare(strict_types=1);

namespace HybridRAG\TextEmbedding;

/**
 * Class DimensionalityReducer
 *
 * This class provides functionality to reduce the dimensionality of vectors using PCA.
 */
class DimensionalityReducer
{
    /**
     * Reduce the dimensions of the given vectors to the target dimensions.
     *
     * @param array $vectors The input vectors to reduce
     * @param int $targetDimensions The desired number of dimensions after reduction
     * @return array The reduced vectors
     */
    public function reduceDimensions(array $vectors, int $targetDimensions): array
    {
        $meanVector = $this->calculateMeanVector($vectors);
        $centeredVectors = $this->centerVectors($vectors, $meanVector);
        $covarianceMatrix = $this->calculateCovarianceMatrix($centeredVectors);
        $eigenVectors = $this->calculateEigenVectors($covarianceMatrix, $targetDimensions);
        
        return $this->projectVectors($centeredVectors, $eigenVectors);
    }

    /**
     * Calculate the mean vector from a set of vectors.
     *
     * @param array $vectors The input vectors
     * @return array The calculated mean vector
     */
    private function calculateMeanVector(array $vectors): array
    {
        $numVectors = count($vectors);
        $numDimensions = count($vectors[0]);
        $meanVector = array_fill(0, $numDimensions, 0);

        foreach ($vectors as $vector) {
            for ($i = 0; $i < $numDimensions; $i++) {
                $meanVector[$i] += $vector[$i];
            }
        }

        for ($i = 0; $i < $numDimensions; $i++) {
            $meanVector[$i] /= $numVectors;
        }

        return $meanVector;
    }

    /**
     * Center the vectors by subtracting the mean vector from each.
     *
     * @param array $vectors The input vectors
     * @param array $meanVector The mean vector to subtract
     * @return array The centered vectors
     */
    private function centerVectors(array $vectors, array $meanVector): array
    {
        return array_map(function($vector) use ($meanVector) {
            return array_map(function($value, $mean) {
                return $value - $mean;
            }, $vector, $meanVector);
        }, $vectors);
    }

    /**
     * Calculate the covariance matrix from the centered vectors.
     *
     * @param array $centeredVectors The centered vectors
     * @return array The calculated covariance matrix
     */
    private function calculateCovarianceMatrix(array $centeredVectors): array
    {
        $numVectors = count($centeredVectors);
        $numDimensions = count($centeredVectors[0]);
        $covarianceMatrix = array_fill(0, $numDimensions, array_fill(0, $numDimensions, 0));

        foreach ($centeredVectors as $vector) {
            for ($i = 0; $i < $numDimensions; $i++) {
                for ($j = 0; $j < $numDimensions; $j++) {
                    $covarianceMatrix[$i][$j] += $vector[$i] * $vector[$j];
                }
            }
        }

        for ($i = 0; $i < $numDimensions; $i++) {
            for ($j = 0; $j < $numDimensions; $j++) {
                $covarianceMatrix[$i][$j] /= $numVectors - 1;
            }
        }

        return $covarianceMatrix;
    }

    /**
     * Calculate the eigenvectors of the covariance matrix.
     *
     * @param array $matrix The covariance matrix
     * @param int $numEigenVectors The number of eigenvectors to calculate
     * @return array The calculated eigenvectors
     */
    private function calculateEigenVectors(array $matrix, int $numEigenVectors): array
    {
        // This is a simplified method and may not be suitable for large matrices
        // For production use, consider using a more robust linear algebra library
        $eigenVectors = [];
        for ($i = 0; $i < $numEigenVectors; $i++) {
            $vector = $this->powerIteration($matrix);
            $eigenVectors[] = $vector;
            $matrix = $this->deflateMatrix($matrix, $vector);
        }
        return $eigenVectors;
    }

    /**
     * Perform power iteration to find the dominant eigenvector of a matrix.
     *
     * @param array $matrix The input matrix
     * @param int $numIterations The number of iterations to perform
     * @return array The dominant eigenvector
     */
    private function powerIteration(array $matrix, int $numIterations = 100): array
    {
        $vector = array_fill(0, count($matrix), 1);
        for ($i = 0; $i < $numIterations; $i++) {
            $vector = $this->matrixVectorMultiply($matrix, $vector);
            $vector = $this->normalizeVector($vector);
        }
        return $vector;
    }

    /**
     * Multiply a matrix by a vector.
     *
     * @param array $matrix The matrix
     * @param array $vector The vector
     * @return array The resulting vector
     */
    private function matrixVectorMultiply(array $matrix, array $vector): array
    {
        $result = array_fill(0, count($vector), 0);
        for ($i = 0; $i < count($matrix); $i++) {
            for ($j = 0; $j < count($vector); $j++) {
                $result[$i] += $matrix[$i][$j] * $vector[$j];
            }
        }
        return $result;
    }

    /**
     * Normalize a vector to unit length.
     *
     * @param array $vector The input vector
     * @return array The normalized vector
     */
    private function normalizeVector(array $vector): array
    {
        $magnitude = sqrt(array_sum(array_map(function($x) { return $x * $x; }, $vector)));
        return array_map(function($x) use ($magnitude) { return $x / $magnitude; }, $vector);
    }

    /**
     * Deflate a matrix by subtracting the outer product of a vector from it.
     *
     * @param array $matrix The input matrix
     * @param array $vector The vector to use for deflation
     * @return array The deflated matrix
     */
    private function deflateMatrix(array $matrix, array $vector): array
    {
        $outer = [];
        for ($i = 0; $i < count($vector); $i++) {
            for ($j = 0; $j < count($vector); $j++) {
                $outer[$i][$j] = $vector[$i] * $vector[$j];
            }
        }
        return array_map(function($row, $outerRow) {
            return array_map(function($value, $outerValue) {
                return $value - $outerValue;
            }, $row, $outerRow);
        }, $matrix, $outer);
    }

    /**
     * Project vectors onto the eigenvectors.
     *
     * @param array $vectors The input vectors
     * @param array $eigenVectors The eigenvectors to project onto
     * @return array The projected vectors
     */
    private function projectVectors(array $vectors, array $eigenVectors): array
    {
        return array_map(function($vector) use ($eigenVectors) {
            return array_map(function($eigenVector) use ($vector) {
                return array_sum(array_map(function($a, $b) { return $a * $b; }, $vector, $eigenVector));
            }, $eigenVectors);
        }, $vectors);
    }
}
