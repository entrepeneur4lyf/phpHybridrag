<?php

declare(strict_types=1);

namespace HybridRAG\TextEmbedding;

class DimensionalityReducer
{
    public function reduceDimensions(array $vectors, int $targetDimensions): array
    {
        $meanVector = $this->calculateMeanVector($vectors);
        $centeredVectors = $this->centerVectors($vectors, $meanVector);
        $covarianceMatrix = $this->calculateCovarianceMatrix($centeredVectors);
        $eigenVectors = $this->calculateEigenVectors($covarianceMatrix, $targetDimensions);
        
        return $this->projectVectors($centeredVectors, $eigenVectors);
    }

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

    private function centerVectors(array $vectors, array $meanVector): array
    {
        return array_map(function($vector) use ($meanVector) {
            return array_map(function($value, $mean) {
                return $value - $mean;
            }, $vector, $meanVector);
        }, $vectors);
    }

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

    private function powerIteration(array $matrix, int $numIterations = 100): array
    {
        $vector = array_fill(0, count($matrix), 1);
        for ($i = 0; $i < $numIterations; $i++) {
            $vector = $this->matrixVectorMultiply($matrix, $vector);
            $vector = $this->normalizeVector($vector);
        }
        return $vector;
    }

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

    private function normalizeVector(array $vector): array
    {
        $magnitude = sqrt(array_sum(array_map(function($x) { return $x * $x; }, $vector)));
        return array_map(function($x) use ($magnitude) { return $x / $magnitude; }, $vector);
    }

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

    private function projectVectors(array $vectors, array $eigenVectors): array
    {
        return array_map(function($vector) use ($eigenVectors) {
            return array_map(function($eigenVector) use ($vector) {
                return array_sum(array_map(function($a, $b) { return $a * $b; }, $vector, $eigenVector));
            }, $eigenVectors);
        }, $vectors);
    }
}