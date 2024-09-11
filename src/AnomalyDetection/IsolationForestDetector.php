<?php

declare(strict_types=1);

namespace HybridRAG\AnomalyDetection;

use Phpml\Preprocessing\Normalizer;
use Phpml\Math\Distance\Euclidean;

class IsolationForestDetector
{
    private int $subsampleSize;
    private int $numTrees;
    private Normalizer $normalizer;
    private Euclidean $distance;

    public function __construct(int $subsampleSize = 256, int $numTrees = 100)
    {
        $this->subsampleSize = $subsampleSize;
        $this->numTrees = $numTrees;
        $this->normalizer = new Normalizer();
        $this->distance = new Euclidean();
    }

    public function fit(array $data): void
    {
        $this->data = $this->normalizer->transform($data);
        $this->trees = [];
        for ($i = 0; $i < $this->numTrees; $i++) {
            $this->trees[] = $this->buildTree($this->data, 0);
        }
    }

    public function predict(array $sample): float
    {
        $sample = $this->normalizer->transform([$sample])[0];
        $pathLengths = [];
        foreach ($this->trees as $tree) {
            $pathLengths[] = $this->traverseTree($tree, $sample, 0);
        }
        $averagePathLength = array_sum($pathLengths) / count($pathLengths);
        return 2 ** (-$averagePathLength / $this->calculateC($this->subsampleSize));
    }

    private function buildTree(array $data, int $depth): array
    {
        if (count($data) <= 1 || $depth >= $this->calculateC($this->subsampleSize)) {
            return ['type' => 'leaf', 'size' => count($data)];
        }

        $featureIndex = random_int(0, count($data[0]) - 1);
        $splitValue = $this->randomBetween(
            min(array_column($data, $featureIndex)),
            max(array_column($data, $featureIndex))
        );

        $left = array_filter($data, fn($point) => $point[$featureIndex] < $splitValue);
        $right = array_filter($data, fn($point) => $point[$featureIndex] >= $splitValue);

        return [
            'type' => 'split',
            'feature' => $featureIndex,
            'value' => $splitValue,
            'left' => $this->buildTree($left, $depth + 1),
            'right' => $this->buildTree($right, $depth + 1),
        ];
    }

    private function traverseTree(array $node, array $sample, int $depth): int
    {
        if ($node['type'] === 'leaf') {
            return $depth;
        }

        if ($sample[$node['feature']] < $node['value']) {
            return $this->traverseTree($node['left'], $sample, $depth + 1);
        } else {
            return $this->traverseTree($node['right'], $sample, $depth + 1);
        }
    }

    private function calculateC(int $n): float
    {
        return 2 * (log($n - 1) + 0.5772156649) - (2 * ($n - 1) / $n);
    }

    private function randomBetween(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}