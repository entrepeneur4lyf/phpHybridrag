<?php

declare(strict_types=1);

namespace HybridRAG\HybridRAG;

use Phpml\Classification\SVC;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;

class NERClassifier
{
    private SVC $classifier;
    private TokenCountVectorizer $vectorizer;

    public function __construct()
    {
        $this->classifier = new SVC(Kernel::RBF, 1.0, 3);
        $this->vectorizer = new TokenCountVectorizer(new WordTokenizer());
    }

    public function train(array $samples, array $labels): void
    {
        $features = $this->vectorizer->fit($samples)->transform($samples);
        $this->classifier->train($features, $labels);
    }

    public function predict(array $samples): array
    {
        $features = $this->vectorizer->transform($samples);
        return $this->classifier->predict($features);
    }
}