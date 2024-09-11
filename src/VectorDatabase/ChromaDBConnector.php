<?php

declare(strict_types=1);

namespace HybridRAG\VectorDatabase;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ChromaDBConnector implements VectorDatabaseInterface
{
    private Client $client;
    private string $collectionName;

    public function __construct(
        private string $host,
        private int $port,
        private string $collection = 'default_collection'
    ) {
        $this->client = new Client([
            'base_uri' => "http://{$this->host}:{$this->port}",
        ]);
        $this->collectionName = $collection;
        $this->createCollectionIfNotExists();
    }

    public function insert(string $id, array $vector, array $metadata = []): void
    {
        $this->request('POST', "/api/v1/collections/{$this->collectionName}/points", [
            'json' => [
                'ids' => [$id],
                'embeddings' => [$vector],
                'metadatas' => [$metadata],
            ],
        ]);
    }

    public function query(array $vector, int $topK = 5, array $filters = []): array
    {
        $queryParams = [
            'vector' => $vector,
            'n_results' => $topK,
        ];

        if (!empty($filters)) {
            $queryParams['where'] = $filters;
        }

        $response = $this->request('POST', "/api/v1/collections/{$this->collectionName}/query", [
            'json' => $queryParams,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function update(string $id, array $vector, array $metadata = []): void
    {
        $this->request('PUT', "/api/v1/collections/{$this->collectionName}/points", [
            'json' => [
                'ids' => [$id],
                'embeddings' => [$vector],
                'metadatas' => [$metadata],
            ],
        ]);
    }

    public function delete(string $id): void
    {
        $this->request('POST', "/api/v1/collections/{$this->collectionName}/points/delete", [
            'json' => [
                'ids' => [$id],
            ],
        ]);
    }

    public function getAllDocuments(): array
    {
        $response = $this->request('POST', "/api/v1/collections/{$this->collectionName}/get", [
            'json' => [
                'limit' => 10000, // Adjust this value based on your needs
            ],
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        return array_map(function ($item) {
            return [
                'id' => $item['id'],
                'content' => $item['metadata']['content'] ?? '',
                'metadata' => $item['metadata'],
            ];
        }, $result['documents'] ?? []);
    }

    private function createCollectionIfNotExists(): void
    {
        try {
            $this->request('GET', "/api/v1/collections/{$this->collectionName}");
        } catch (GuzzleException $e) {
            if ($e->getCode() === 404) {
                $this->request('POST', '/api/v1/collections', [
                    'json' => [
                        'name' => $this->collectionName,
                    ],
                ]);
            } else {
                throw $e;
            }
        }
    }

    private function request(string $method, string $uri, array $options = [])
    {
        try {
            return $this->client->request($method, $uri, $options);
        } catch (GuzzleException $e) {
            // Log the error or handle it as needed
            throw new \RuntimeException("ChromaDB request failed: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}