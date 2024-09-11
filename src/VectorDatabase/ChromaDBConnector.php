<?php

declare(strict_types=1);

namespace HybridRAG\VectorDatabase;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use HybridRAG\Config\Configuration;
/**
 * Class ChromaDBConnector
 *
 * This class provides a connection to ChromaDB and implements the VectorDatabaseInterface.
 */
class ChromaDBConnector implements VectorDatabaseInterface
{
    private Client $client;
    private string $collectionName;

    /**
     * ChromaDBConnector constructor.
     *
     * @param Configuration $config The configuration object
     */
    public function __construct(
        private Configuration $config
    ) {
        $this->client = new Client([
            'base_uri' => "http://{$this->config->chromadb['host']}:{$this->config->chromadb['port']}",
        ]);
        $this->collectionName = $this->config->chromadb['collection'];
        $this->createCollectionIfNotExists();
    }

    /**
     * Insert a vector into the ChromaDB collection.
     *
     * @param string $id The unique identifier for the vector
     * @param array $vector The vector to insert
     * @param array $metadata Additional metadata associated with the vector
     * @throws \RuntimeException If the insertion fails
     */
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

    /**
     * Query the ChromaDB collection for similar vectors.
     *
     * @param array $vector The query vector
     * @param int $topK The number of top results to return
     * @param array $filters Additional filters to apply to the query
     * @return array An array of similar vectors and their metadata
     * @throws \RuntimeException If the query fails
     */
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

    /**
     * Update an existing vector in the ChromaDB collection.
     *
     * @param string $id The unique identifier of the vector to update
     * @param array $vector The new vector data
     * @param array $metadata The new metadata associated with the vector
     * @throws \RuntimeException If the update fails
     */
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

    /**
     * Delete a vector from the ChromaDB collection.
     *
     * @param string $id The unique identifier of the vector to delete
     * @throws \RuntimeException If the deletion fails
     */
    public function delete(string $id): void
    {
        $this->request('POST', "/api/v1/collections/{$this->collectionName}/points/delete", [
            'json' => [
                'ids' => [$id],
            ],
        ]);
    }

    /**
     * Retrieve all documents from the ChromaDB collection.
     *
     * @return array An array of all documents in the collection
     * @throws \RuntimeException If the retrieval fails
     */
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

    /**
     * Create the collection if it doesn't exist.
     *
     * @throws \RuntimeException If the creation fails
     */
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

    /**
     * Make a request to the ChromaDB API.
     *
     * @param string $method The HTTP method
     * @param string $uri The URI
     * @param array $options Additional options for the request
     * @return \Psr\Http\Message\ResponseInterface The response
     * @throws \RuntimeException If the request fails
     */
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
