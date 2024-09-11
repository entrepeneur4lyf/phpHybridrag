<?php

declare(strict_types=1);

namespace HybridRAG\TextEmbedding;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\SimpleCache\CacheInterface;
use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\FeatureExtraction\StopWords\English;

/**
 * Class OpenAIEmbedding
 *
 * This class implements the EmbeddingInterface using Open AI's text embedding API
 * with enhanced text preprocessing.
 */
class OpenAIEmbedding implements EmbeddingInterface
{
    private Client $client;
    private CacheInterface $cache;
    private TfIdfTransformer $tfidfTransformer;
    private WordTokenizer $tokenizer;
    private English $stopWords;

    /**
     * OpenAIEmbedding constructor.
     *
     * @param string $apiKey The OpenAI API key
     * @param string $model The embedding model to use (default: 'text-embedding-ada-002')
     * @param int $cacheTtl The cache time-to-live in seconds (default: 86400 seconds / 24 hours)
     * @param CacheInterface|null $cache The cache implementation to use (default: ArrayCache)
     */
    public function __construct(
        private string $apiKey,
        private string $model = 'text-embedding-ada-002',
        private int $cacheTtl = 86400, // 24 hours
        ?CacheInterface $cache = null
    ) {
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
        ]);
        $this->cache = $cache ?? new ArrayCache();
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
        $cacheKey = $this->getCacheKey($text);
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $enhancedText = $this->enhanceText($text);
        $embedding = $this->callOpenAIAPI([$enhancedText])[0];
        $this->cache->set($cacheKey, $embedding, $this->cacheTtl);

        return $embedding;
    }

    /**
     * Embed multiple texts into vector representations with enhanced preprocessing.
     *
     * @param array $texts An array of texts to embed
     * @return array An array of vector representations for the input texts
     */
    public function embedBatch(array $texts): array
    {
        $uncachedTexts = [];
        $embeddings = [];

        foreach ($texts as $index => $text) {
            $cacheKey = $this->getCacheKey($text);
            if ($this->cache->has($cacheKey)) {
                $embeddings[$index] = $this->cache->get($cacheKey);
            } else {
                $uncachedTexts[$index] = $this->enhanceText($text);
            }
        }

        if (!empty($uncachedTexts)) {
            $newEmbeddings = $this->callOpenAIAPI(array_values($uncachedTexts));
            foreach ($uncachedTexts as $index => $text) {
                $embedding = $newEmbeddings[array_search($text, $uncachedTexts)];
                $embeddings[$index] = $embedding;
                $this->cache->set($this->getCacheKey($texts[$index]), $embedding, $this->cacheTtl);
            }
        }

        ksort($embeddings);
        return $embeddings;
    }

    /**
     * Enhance the input text by preprocessing and applying TF-IDF transformation.
     *
     * @param string $text The input text to enhance
     * @return string The enhanced text
     */
    private function enhanceText(string $text): string
    {
        $tokens = $this->preprocess($text);
        $tfidf = $this->tfidfTransformer->transform([$tokens]);
        return implode(' ', array_keys(array_filter($tfidf[0])));
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

    /**
     * Call the OpenAI API to get embeddings for the given texts.
     *
     * @param array $texts The texts to embed
     * @return array The embeddings returned by the API
     * @throws \RuntimeException If the API request fails
     */
    private function callOpenAIAPI(array $texts): array
    {
        try {
            $response = $this->client->post('embeddings', [
                'json' => [
                    'model' => $this->model,
                    'input' => $texts,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            return array_column($result['data'], 'embedding');
        } catch (GuzzleException $e) {
            throw new \RuntimeException("OpenAI API request failed: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Generate a cache key for the given text.
     *
     * @param string $text The text to generate a cache key for
     * @return string The generated cache key
     */
    private function getCacheKey(string $text): string
    {
        return 'embedding_' . md5($text);
    }
}
