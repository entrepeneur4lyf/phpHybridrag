<?php

declare(strict_types=1);

namespace HybridRAG\TextClassification;

use Phpml\Classification\SVC;
use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\CrossValidation\StratifiedRandomSplit;
use Phpml\Metric\Accuracy;

/**
 * Class TextClassifier
 *
 * This class provides text classification functionality using Support Vector Classification (SVC).
 */
/**
 * Class TextClassifier
 *
 * This class provides text classification functionality using Support Vector Classification (SVC).
 */
class TextClassifier
{
    private SVC $classifier;
    private TfIdfTransformer $tfidfTransformer;
    private WordTokenizer $tokenizer;

    /**
     * TextClassifier constructor.
     *
     * Initializes the SVC classifier, TF-IDF transformer, and word tokenizer.
     */
    public function __construct()
    {
        $this->classifier = new SVC();
        $this->tfidfTransformer = new TfIdfTransformer();
        $this->tokenizer = new WordTokenizer();
    }

    /**
     * Train the classifier with the given samples and labels.
     *
     * @param array $samples An array of text samples.
     * @param array $labels An array of corresponding labels.
     */
    public function train(array $samples, array $labels): void
    {
        $tokens = array_map([$this, 'tokenize'], $samples);
        $features = $this->tfidfTransformer->fit($tokens)->transform($tokens);
        $this->classifier->train($features, $labels);
    }

    /**
     * Predict the label for a given text.
     *
     * @param string $text The text to classify.
     * @return string The predicted label.
     */
    public function predict(string $text): string
    {
        $tokens = $this->tokenize($text);
        $features = $this->tfidfTransformer->transform([$tokens]);
        return $this->classifier->predict($features[0]);
    }

    /**
     * Evaluate the classifier's performance using stratified random split.
     *
     * @param array $samples An array of text samples.
     * @param array $labels An array of corresponding labels.
     * @return float The accuracy score of the classifier.
     */
    public function evaluate(array $samples, array $labels): float
    {
        $dataset = new StratifiedRandomSplit($samples, $labels, 0.2);
        $this->train($dataset->getTrainSamples(), $dataset->getTrainLabels());
        
        $predictions = $this->classifier->predict($dataset->getTestSamples());
        return Accuracy::score($dataset->getTestLabels(), $predictions);
    }

    /**
     * Tokenize the given text.
     *
     * @param string $text The text to tokenize.
     * @return array An array of tokens.
     */
    private function tokenize(string $text): array
    {
        return $this->tokenizer->tokenize(strtolower($text));
    }
}
