<?php

declare(strict_types=1);

namespace HybridRAG\TextEmbedding;

use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\FeatureExtraction\StopWords\English;

/**
 * Class EnhancedOpenAIEmbedding
 *
 * This class extends the OpenAIEmbedding class to provide enhanced text preprocessing
 * before generating embeddings.
 */
class EnhancedOpenAIEmbedding extends OpenAIEmbedding
{
    private TfIdfTransformer $tfidfTransformer;
    private WordTokenizer $tokenizer;
    private English $stopWords;

    /**
     * EnhancedOpenAIEmbedding constructor.
     *
     * @param string $apiKey The OpenAI API key
     * @param string $model The embedding model to use (default: 'text-embedding-ada-002')
     */
    public function __construct(string $apiKey, string $model = 'text-embedding-ada-002')
    {
        parent::__construct($apiKey, $model);
        $this->tfidfTransformer = new TfIdfTransformer();
        $this->tokenizer = new WordTokenizer();
        $this->stopWords = new English();
    }

    /**
     * Embed a single text into a vector representation with enhanced preprocessing.
     *
     * @param string $text The text to embed
     * @return array The vector representation of the text
     */
    public function embed(string $text): array
    {
        $tokens = $this->preprocess($text);
        $tfidf = $this->tfidfTransformer->transform([$tokens]);
        $enhancedText = implode(' ', array_keys(array_filter($tfidf[0])));
        return parent::embed($enhancedText);
    }

    /**
     * Preprocess the input text by tokenizing, converting to lowercase,
     * and removing stop words.
     *
     * @param string $text The input text to preprocess
     * @return array The preprocessed tokens
     */
    private function preprocess(string $text): array
    {
        $tokens = $this->tokenizer->tokenize(strtolower($text));
        return array_filter($tokens, fn($token) => !$this->stopWords->isStopWord($token));
    }
}
