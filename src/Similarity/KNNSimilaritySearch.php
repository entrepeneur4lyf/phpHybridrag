<?php

declare(strict_types=1);

namespace HybridRAG\Similarity;

use Phpml\Metric\Distance;
use Phpml\Metric\Distance\Euclidean;

class KNNSimilaritySearch
{
    private array $data;
    private Distance $distance;

    public function __construct(Distance $distance = null)
    {
        $this->distance = $distance ?? new Euclidean();
    }

    public function fit(array $data): void
    {
        $this->data = $data;
    }

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