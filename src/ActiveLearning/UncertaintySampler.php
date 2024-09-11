<?php

declare(strict_types=1);

namespace HybridRAG\ActiveLearning;

use Phpml\Classification\Classifier;

/**
 * Class UncertaintySampler
 *
 * This class implements uncertainty sampling for active learning.
 */
class UncertaintySampler
{
    private Classifier $classifier;

    /**
     * UncertaintySampler constructor.
     *
     * @param Classifier $classifier The classifier to use for uncertainty sampling
     */
    public function __construct(Classifier $classifier)
    {
        $this->classifier = $classifier;
    }

    /**
     * Select samples for labeling based on uncertainty.
     *
     * @param array $unlabeledSamples The pool of unlabeled samples
     * @param int $numSamples The number of samples to select
     * @return array The selected samples
     */
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
