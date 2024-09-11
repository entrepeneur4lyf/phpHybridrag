<?php

declare(strict_types=1);

namespace HybridRAG\ActiveLearning;

use Phpml\Classification\Classifier;

class UncertaintySampler
{
    private Classifier $classifier;

    public function __construct(Classifier $classifier)
    {
        $this->classifier = $classifier;
    }

    public function selectSamples(array $unlabeledSamples, int $numSamples): array
    {
        $uncertainties = [];
        foreach ($unlabeledSamples as $index => $sample) {
            $probabilities = $this->classifier->predictProbability($sample);
            $uncertainty = 1 - max($probabilities);
            $uncertainties[$index] = $uncertainty;
        }

        arsort($uncertainties);
        $selectedIndices = array_slice(array_keys($uncertainties), 0, $numSamples);

        $selectedSamples = [];
        foreach ($selectedIndices as $index) {
            $selectedSamples[] = $unlabeledSamples[$index];
        }

        return $selectedSamples;
    }
}