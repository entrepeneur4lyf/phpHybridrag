<?php

declare(strict_types=1);

namespace HybridRAG\TextProcessing;

use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\FeatureExtraction\StopWords\English;

/**
 * Class AdvancedFeatureExtractor
 *
 * This class provides advanced feature extraction capabilities for text processing.
 */
class AdvancedFeatureExtractor
{
    private TokenCountVectorizer $vectorizer;
    private TfIdfTransformer $transformer;

    /**
     * AdvancedFeatureExtractor constructor.
     *
     * @param int $minDf Minimum document frequency for a term to be included
     * @param int $maxDf Maximum document frequency for a term to be included
     */
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

    /**
     * Extract features from the given documents.
     *
     * @param array $documents An array of documents to extract features from
     * @return array The extracted features as TF-IDF vectors
     */
    public function extractFeatures(array $documents): array
    {
        $counts = $this->vectorizer->fit($documents)->transform($documents);
        return $this->transformer->fit($counts)->transform($counts);
    }

    /**
     * Get the vocabulary used by the vectorizer.
     *
     * @return array The vocabulary as an array of terms
     */
    public function getVocabulary(): array
    {
        return $this->vectorizer->getVocabulary();
    }
}
