<?php

declare(strict_types=1);

namespace HybridRAG\VectorRAG;

/**
 * Interface VectorRAGInterface
 *
 * Defines the contract for Vector-based Retrieval-Augmented Generation (RAG) operations.
 */
interface VectorRAGInterface
{
    /**
     * Add a document to the vector database.
     *
     * @param string $id The unique identifier for the document
     * @param string $content The content of the document
     * @param array $metadata Additional metadata associated with the document
     */
    public function addDocument(string $id, string $content, array $metadata = []): void;

    /**
     * Retrieve context for a given query.
     *
     * @param string $query The query string
     * @param int $topK The number of top results to return
     * @param array $filters Additional filters to apply to the query
     * @return array An array of relevant context
     */
    public function retrieveContext(string $query, int $topK = 5, array $filters = []): array;

    /**
     * Generate an answer based on the query and provided context.
     *
     * @param string $query The query string
     * @param array $context The context retrieved for the query
     * @return string The generated answer
     */
    public function generateAnswer(string $query, array $context): string;
}
