<?php

declare(strict_types=1);

namespace HybridRAG\GraphRAG;

/**
 * Interface GraphRAGInterface
 *
 * Defines the contract for Graph-based Retrieval-Augmented Generation (RAG) operations.
 */
interface GraphRAGInterface
{
    /**
     * Add an entity to the knowledge graph.
     *
     * @param string $id The unique identifier for the entity
     * @param string $content The content of the entity
     * @param array $metadata Additional metadata associated with the entity
     * @return string The ID of the added entity
     */
    public function addEntity(string $id, string $content, array $metadata = []): string;

    /**
     * Add a relationship between two entities in the knowledge graph.
     *
     * @param string $fromId The ID of the source entity
     * @param string $toId The ID of the target entity
     * @param string $type The type of the relationship
     * @param array $attributes Additional attributes for the relationship
     * @return string The ID of the added relationship
     */
    public function addRelationship(string $fromId, string $toId, string $type, array $attributes = []): string;

    /**
     * Retrieve context for a given query from the knowledge graph.
     *
     * @param string $query The query string
     * @param int|null $maxDepth The maximum depth to traverse in the graph (optional)
     * @return array An array of relevant context from the graph
     */
    public function retrieveContext(string $query, int $maxDepth = null): array;

    /**
     * Generate an answer based on the query and provided context from the graph.
     *
     * @param string $query The query string
     * @param array $context The context retrieved from the graph
     * @return string The generated answer
     */
    public function generateAnswer(string $query, array $context): string;
}
