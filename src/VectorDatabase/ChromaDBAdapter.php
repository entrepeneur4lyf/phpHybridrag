<?php

declare(strict_types=1);

namespace HybridRAG\VectorDatabase;

use CodeWithKyrian\ChromaDB\ChromaDB;
use CodeWithKyrian\ChromaDB\Collection;
use HybridRAG\Exception\HybridRAGException;
use HybridRAG\Logging\Logger;

class ChromaDBAdapter implements VectorDatabaseInterface
{
    private ChromaDB $client;
    private Collection $collection;
    private Logger $logger;

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
}