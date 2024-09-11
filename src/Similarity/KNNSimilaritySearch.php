<?php

declare(strict_types=1);

namespace HybridRAG\Similarity;

use Phpml\Metric\Distance;
use Phpml\Metric\Distance\Euclidean;

/**
 * Class KNNSimilaritySearch
 *
 * This class implements a K-Nearest Neighbors (KNN) similarity search algorithm.
 */
class KNNSimilaritySearch
{
    private array $data;
    private Distance $distance;

    /**
     * KNNSimilaritySearch constructor.
     *
     * @param Distance|null $distance The distance metric to use (default: Euclidean)
     */
    public function __construct(Distance $distance = null)
    {
        $this->distance = $distance ?? new Euclidean();
    }

    /**
     * Fit the KNN model with the given data.
     *
     * @param array $data The data to fit the model with
     */
    public function fit(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Find the K nearest neighbors for a given query point.
     *
     * @param array $query The query point
     * @param int $k The number of nearest neighbors to find
     * @return array The K nearest neighbors with their indices, points, and distances
     */
    public function findNearest(array $query, int $k): array
    {
        $distances = [];
        foreach ($this->data as $index => $point) {
            $distances[$index] = $this->distance->distance($query, $point);
        }

        asort($distances);
        $nearestIndices = array_slice(array_keys($distances), 0, $k);

        $nearest = [];
        foreach ($nearestIndices as $index) {
            $nearest[] = [
                'index' => $index,
                'point' => $this->data[$index],
                'distance' => $distances[$index],
            ];
        }

        return $nearest;
    }
}
