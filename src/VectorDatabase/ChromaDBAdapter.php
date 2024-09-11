<?php

declare(strict_types=1);

namespace HybridRAG\VectorDatabase;

use CodeWithKyrian\ChromaDB\ChromaDB;
use CodeWithKyrian\ChromaDB\Collection;
use HybridRAG\Exception\HybridRAGException;
use HybridRAG\Logging\Logger;

/**
 * Class ChromaDBAdapter
 *
 * This class adapts the ChromaDB client to the VectorDatabaseInterface.
 */
class ChromaDBAdapter implements VectorDatabaseInterface
{
    private ChromaDB $client;
    private Collection $collection;
    private Logger $logger;

    /**
     * ChromaDBAdapter constructor.
     *
     * @param array $config The configuration array for ChromaDB
     * @param Logger $logger The logger instance
     * @throws HybridRAGException If connection to ChromaDB fails
     */
    public function __construct(array $config, Logger $logger)
    {
        $this->logger = $logger;
        try {
            $this->client = new ChromaDB($config['host'], $config['port']);
            $this->collection = $this->client->getOrCreateCollection($config['collection']);
            $this->logger->info("ChromaDB connection established", ['collection' => $config['collection']]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to connect to ChromaDB", ['error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to connect to ChromaDB: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Insert a vector into the ChromaDB collection.
     *
     * @param string $id The unique identifier for the vector
     * @param array $vector The vector to insert
     * @param array $metadata Additional metadata associated with the vector
     * @throws HybridRAGException If insertion fails
     */
    public function insert(string $id, array $vector, array $metadata = []): void
    {
        try {
            $this->collection->add(
                ids: [$id],
                embeddings: [$vector],
                metadatas: [$metadata]
            );
            $this->logger->info("Vector inserted into ChromaDB", ['id' => $id]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to insert vector into ChromaDB", ['id' => $id, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to insert vector into ChromaDB: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Query the ChromaDB collection for similar vectors.
     *
     * @param array $vector The query vector
     * @param int $topK The number of top results to return
     * @param array $filters Additional filters to apply to the query
     * @return array An array of similar vectors and their metadata
     * @throws HybridRAGException If querying fails
     */
    public function query(array $vector, int $topK, array $filters = []): array
    {
        try {
            $result = $this->collection->query(
                queryEmbeddings: [$vector],
                nResults: $topK,
                whereDocument: $filters
            );
            $this->logger->info("Query executed on ChromaDB", ['topK' => $topK]);
            return $this->formatQueryResult($result);
        } catch (\Exception $e) {
            $this->logger->error("Failed to query ChromaDB", ['error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to query ChromaDB: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Format the query result from ChromaDB into a standardized format.
     *
     * @param array $result The raw query result from ChromaDB
     * @return array The formatted query result
     */
    private function formatQueryResult(array $result): array
    {
        $formattedResult = [];
        foreach ($result['ids'][0] as $index => $id) {
            $formattedResult[] = [
                'id' => $id,
                'vector' => $result['embeddings'][0][$index],
                'metadata' => $result['metadatas'][0][$index],
                'distance' => $result['distances'][0][$index],
            ];
        }
        return $formattedResult;
    }

    /**
     * Update an existing vector in the ChromaDB collection.
     *
     * @param string $id The unique identifier of the vector to update
     * @param array $vector The new vector data
     * @param array $metadata The new metadata associated with the vector
     * @throws HybridRAGException If update fails
     */
    public function update(string $id, array $vector, array $metadata = []): void
    {
        try {
            $this->collection->update(
                ids: [$id],
                embeddings: [$vector],
                metadatas: [$metadata]
            );
            $this->logger->info("Vector updated in ChromaDB", ['id' => $id]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to update vector in ChromaDB", ['id' => $id, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to update vector in ChromaDB: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a vector from the ChromaDB collection.
     *
     * @param string $id The unique identifier of the vector to delete
     * @throws HybridRAGException If deletion fails
     */
    public function delete(string $id): void
    {
        try {
            $this->collection->delete(ids: [$id]);
            $this->logger->info("Vector deleted from ChromaDB", ['id' => $id]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete vector from ChromaDB", ['id' => $id, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to delete vector from ChromaDB: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Retrieve all documents from the ChromaDB collection.
     *
     * @return array An array of all documents in the collection
     * @throws HybridRAGException If retrieval fails
     */
    public function getAllDocuments(): array
    {
        try {
            $result = $this->collection->get();
            $this->logger->info("Retrieved all documents from ChromaDB", ['count' => count($result['ids'])]);
            return $this->formatQueryResult($result);
        } catch (\Exception $e) {
            $this->logger->error("Failed to retrieve all documents from ChromaDB", ['error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to retrieve all documents from ChromaDB: " . $e->getMessage(), 0, $e);
        }
    }
}
