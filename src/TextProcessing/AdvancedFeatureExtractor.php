<?php

declare(strict_types=1);

namespace HybridRAG\TextProcessing;

use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\FeatureExtraction\StopWords\English;

class AdvancedFeatureExtractor
{
    private TokenCountVectorizer $vectorizer;
    private TfIdfTransformer $transformer;

    public function __construct(int $minDf = 1, int $maxDf = 1000)
    {
        $this->vectorizer = new TokenCountVectorizer(
            new WordTokenizer(),
            new English(),
            $minDf,
            $maxDf
        );
        $this->transformer = new TfIdfTransformer();
    }

    public function extractFeatures(array $documents): array
    {
        $counts = $this->vectorizer->fit($documents)->transform($documents);
        return $this->transformer->fit($counts)->transform($counts);
    }

    public function getVocabulary(): array
    {
        return $this->vectorizer->getVocabulary();
    }
}