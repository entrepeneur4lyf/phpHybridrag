<?php

declare(strict_types=1);

namespace HybridRAG\TextEmbedding;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\SimpleCache\CacheInterface;

class OpenAIEmbedding implements EmbeddingInterface
{
    private Client $client;
    private CacheInterface $cache;

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

    private function getCacheKey(string $text): string
    {
        return 'embedding_' . md5($text);
    }
}