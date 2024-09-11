<?php

declare(strict_types=1);

namespace HybridRAG\TextEmbedding;

use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\FeatureExtraction\StopWords\English;

class EnhancedOpenAIEmbedding extends OpenAIEmbedding
{
    private TfIdfTransformer $tfidfTransformer;
    private WordTokenizer $tokenizer;
    private English $stopWords;

    public function __construct(string $apiKey, string $model = 'text-embedding-ada-002')
    {
        parent::__construct($apiKey, $model);
        $this->tfidfTransformer = new TfIdfTransformer();
        $this->tokenizer = new WordTokenizer();
        $this->stopWords = new English();
    }

    public function embed(string $text): array
    {
        $tokens = $this->preprocess($text);
        $tfidf = $this->tfidfTransformer->transform([$tokens]);
        $enhancedText = implode(' ', array_keys(array_filter($tfidf[0])));
        return parent::embed($enhancedText);
    }

    private function preprocess(string $text): array
    {
        $tokens = $this->tokenizer->tokenize(strtolower($text));
        return array_filter($tokens, fn($token) => !$this->stopWords->isStopWord($token));
    }
}