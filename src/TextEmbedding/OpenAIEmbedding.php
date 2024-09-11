<?php

declare(strict_types=1);

namespace HybridRAG\TextEmbedding;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\SimpleCache\CacheInterface;

/**
 * Class OpenAIEmbedding
 *
 * This class implements the EmbeddingInterface using Open AI's text embedding API.
 */
class OpenAIEmbedding implements EmbeddingInterface
{
    private Client $client;
    private CacheInterface $cache;

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
    }

    /**
     * Embed a single text into a vector representation.
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

        $embedding = $this->callOpenAIAPI([$text])[0];
        $this->cache->set($cacheKey, $embedding, $this->cacheTtl);

        return $embedding;
    }

    /**
     * Embed multiple texts into vector representations.
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
                $uncachedTexts[$index] = $text;
            }
        }

        if (!empty($uncachedTexts)) {
            $newEmbeddings = $this->callOpenAIAPI(array_values($uncachedTexts));
            foreach ($uncachedTexts as $index => $text) {
                $embedding = $newEmbeddings[array_search($text, $uncachedTexts)];
                $embeddings[$index] = $embedding;
                $this->cache->set($this->getCacheKey($text), $embedding, $this->cacheTtl);
            }
        }

        ksort($embeddings);
        return $embeddings;
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
