<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

/**
 * Interface GraphDatabaseInterface
 *
 * Defines the contract for graph database operations in the HybridRAG system.
 */
interface GraphDatabaseInterface
{
    /**
     * Connect to the graph database.
     *
     * @param array $config Configuration parameters for the connection.
     */
    public function connect(array $config): void;

    /**
     * Add a node to the graph database.
     *
     * @param string $collection The collection to add the node to.
     * @param array $properties The properties of the node.
     * @return string The ID of the newly added node.
     */
    public function addNode(string $collection, array $properties): string;

    /**
     * Add an edge to the graph database.
     *
     * @param string $collection The collection to add the edge to.
     * @param string $fromId The ID of the source node.
     * @param string $toId The ID of the target node.
     * @param array $properties The properties of the edge.
     * @return string The ID of the newly added edge.
     */
    public function addEdge(string $collection, string $fromId, string $toId, array $properties): string;

    /**
     * Retrieve a node from the graph database.
     *
     * @param string $id The ID of the node to retrieve.
     * @return array|null The node data, or null if not found.
     */
    public function getNode(string $id): ?array;

    /**
     * Retrieve an edge from the graph database.
     *
     * @param string $id The ID of the edge to retrieve.
     * @return array|null The edge data, or null if not found.
     */
    public function getEdge(string $id): ?array;

    /**
     * Update a node in the graph database.
     *
     * @param string $id The ID of the node to update.
     * @param array $properties The new properties for the node.
     */
    public function updateNode(string $id, array $properties): void;

    /**
     * Update an edge in the graph database.
     *
     * @param string $id The ID of the edge to update.
     * @param array $properties The new properties for the edge.
     */
    public function updateEdge(string $id, array $properties): void;

    /**
     * Execute a query on the graph database.
     *
     * @param string $query The query to execute.
     * @param array $bindVars Variables to bind to the query.
     * @return array The query results.
     */
    public function query(string $query, array $bindVars = []): array;

    /**
     * Create an index in the graph database.
     *
     * @param string $collection The collection to create the index on.
     * @param array $fields The fields to include in the index.
     * @param string $type The type of index to create.
     * @param bool $unique Whether the index should enforce uniqueness.
     */
    public function createIndex(string $collection, array $fields, string $type, bool $unique): void;

    /**
     * Backup the graph database.
     *
     * @param string $path The path to store the backup.
     */
    public function backup(string $path): void;

    /**
     * Restore the graph database from a backup.
     *
     * @param string $path The path to the backup file.
     */
    public function restore(string $path): void;

    /**
     * Optimize the graph database.
     */
    public function optimize(): void;
}
