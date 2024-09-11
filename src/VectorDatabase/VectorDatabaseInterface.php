<?php

declare(strict_types=1);

namespace HybridRAG\VectorDatabase;

/**
 * Interface VectorDatabaseInterface
 *
 * Defines the contract for vector database operations in the HybridRAG system.
 */
interface VectorDatabaseInterface
{
    /**
     * Insert a vector into the database.
     *
     * @param string $id The unique identifier for the vector.
     * @param array $vector The vector to insert.
     * @param array $metadata Additional metadata associated with the vector.
     */
    public function insert(string $id, array $vector, array $metadata = []): void;

    /**
     * Query the database for similar vectors.
     *
     * @param array $vector The query vector.
     * @param int $topK The number of top results to return.
     * @param array $filters Additional filters to apply to the query.
     * @return array An array of similar vectors and their metadata.
     */
    public function query(array $vector, int $topK = 5, array $filters = []): array;

    /**
     * Update an existing vector in the database.
     *
     * @param string $id The unique identifier of the vector to update.
     * @param array $vector The new vector data.
     * @param array $metadata The new metadata associated with the vector.
     */
    public function update(string $id, array $vector, array $metadata = []): void;

    /**
     * Delete a vector from the database.
     *
     * @param string $id The unique identifier of the vector to delete.
     */
    public function delete(string $id): void;

    /**
     * Retrieve all documents from the database.
     *
     * @return array An array of all documents in the database.
     */
    public function getAllDocuments(): array;
}
