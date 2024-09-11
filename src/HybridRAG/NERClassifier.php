<?php

declare(strict_types=1);

namespace HybridRAG\HybridRAG;

use Phpml\Classification\SVC;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;

/**
 * Class NERClassifier
 *
 * This class implements a Named Entity Recognition (NER) classifier using Support Vector Classification (SVC).
 */
class NERClassifier
{
    private SVC $classifier;
    private TokenCountVectorizer $vectorizer;

    /**
     * NERClassifier constructor.
     *
     * Initializes the SVC classifier and the TokenCountVectorizer.
     */
    public function __construct()
    {
        $this->classifier = new SVC(Kernel::RBF, 1.0, 3);
        $this->vectorizer = new TokenCountVectorizer(new WordTokenizer());
    }

    /**
     * Train the NER classifier with the given samples and labels.
     *
     * @param array $samples An array of text samples
     * @param array $labels An array of corresponding labels
     */
    public function train(array $samples, array $labels): void
    {
        $features = $this->vectorizer->fit($samples)->transform($samples);
        $this->classifier->train($features, $labels);
    }

    /**
     * Predict the labels for the given samples.
     *
     * @param array $samples An array of text samples to predict
     * @return array An array of predicted labels
     */
    public function predict(array $samples): array
    {
        $features = $this->vectorizer->transform($samples);
        return $this->classifier->predict($features);
    }
}
