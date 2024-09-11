<?php

declare(strict_types=1);

namespace HybridRAG\TextClassification;

use Phpml\Classification\SVC;
use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\CrossValidation\StratifiedRandomSplit;
use Phpml\Metric\Accuracy;

class TextClassifier
{
    private SVC $classifier;
    private TfIdfTransformer $tfidfTransformer;
    private WordTokenizer $tokenizer;

    public function __construct()
    {
        $this->classifier = new SVC();
        $this->tfidfTransformer = new TfIdfTransformer();
        $this->tokenizer = new WordTokenizer();
    }

    public function train(array $samples, array $labels): void
    {
        $tokens = array_map([$this, 'tokenize'], $samples);
        $features = $this->tfidfTransformer->fit($tokens)->transform($tokens);
        $this->classifier->train($features, $labels);
    }

    public function predict(string $text): string
    {
        $tokens = $this->tokenize($text);
        $features = $this->tfidfTransformer->transform([$tokens]);
        return $this->classifier->predict($features[0]);
    }

    public function evaluate(array $samples, array $labels): float
    {
        $dataset = new StratifiedRandomSplit($samples, $labels, 0.2);
        $this->train($dataset->getTrainSamples(), $dataset->getTrainLabels());
        
        $predictions = $this->classifier->predict($dataset->getTestSamples());
        return Accuracy::score($dataset->getTestLabels(), $predictions);
    }

    private function tokenize(string $text): array
    {
        return $this->tokenizer->tokenize(strtolower($text));
    }
}