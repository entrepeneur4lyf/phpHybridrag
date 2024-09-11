<?php

declare(strict_types=1);

namespace HybridRAG\AnomalyDetection;

use Phpml\Preprocessing\Normalizer;
use Phpml\Math\Distance\Euclidean;

/**
 * Class IsolationForestDetector
 *
 * This class implements the Isolation Forest algorithm for anomaly detection.
 */
class IsolationForestDetector
{
    private int $subsampleSize;
    private int $numTrees;
    private Normalizer $normalizer;
    private Euclidean $distance;
    private array $data;
    private array $trees;

    /**
     * IsolationForestDetector constructor.
     *
     * @param int $subsampleSize The size of the subsample to use for each tree
     * @param int $numTrees The number of trees in the forest
     */
    public function __construct(int $subsampleSize = 256, int $numTrees = 100)
    {
        $this->subsampleSize = $subsampleSize;
        $this->numTrees = $numTrees;
        $this->normalizer = new Normalizer();
        $this->distance = new Euclidean();
    }

    /**
     * Fit the Isolation Forest model to the given data.
     *
     * @param array $data The input data to fit the model to
     */
    public function fit(array $data): void
    {
        $this->data = $this->normalizer->transform($data);
        $this->trees = [];
        for ($i = 0; $i < $this->numTrees; $i++) {
            $this->trees[] = $this->buildTree($this->data, 0);
        }
    }

    /**
     * Predict the anomaly score for a given sample.
     *
     * @param array $sample The sample to predict the anomaly score for
     * @return float The anomaly score
     */
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

    /**
     * Build a single isolation tree.
     *
     * @param array $data The data to build the tree from
     * @param int $depth The current depth of the tree
     * @return array The built tree
     */
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

    /**
     * Traverse the isolation tree for a given sample.
     *
     * @param array $node The current node in the tree
     * @param array $sample The sample to traverse the tree with
     * @param int $depth The current depth in the tree
     * @return int The path length for this sample
     */
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

    /**
     * Calculate the normalization factor C(n).
     *
     * @param int $n The size of the sample
     * @return float The calculated C(n) value
     */
    private function calculateC(int $n): float
    {
        return 2 * (log($n - 1) + 0.5772156649) - (2 * ($n - 1) / $n);
    }

    /**
     * Generate a random number between min and max.
     *
     * @param float $min The minimum value
     * @param float $max The maximum value
     * @return float A random number between min and max
     */
    private function randomBetween(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}
