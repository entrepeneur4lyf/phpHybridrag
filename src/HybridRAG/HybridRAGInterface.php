<?php

declare(strict_types=1);

namespace HybridRAG\HybridRAG;

/**
 * Interface HybridRAGInterface
 *
 * Defines the contract for Hybrid Retrieval-Augmented Generation (RAG) operations.
 */
interface HybridRAGInterface
{
    /**
     * Add a document to the system.
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
     * @return array An array of relevant context
     */
    public function retrieveContext(string $query): array;

    /**
     * Generate an answer based on the query and provided context.
     *
     * @param string $query The query string
     * @param array $context The context retrieved for the query
     * @return string The generated answer
     */
    public function generateAnswer(string $query, array $context): string;

    /**
     * Improve the model using active learning techniques.
     *
     * @param array $unlabeledSamples An array of unlabeled samples
     * @param int $numSamples The number of samples to select for improvement
     * @return array The selected samples for improvement
     */
    public function improveModel(array $unlabeledSamples, int $numSamples): array;

    /**
     * Evaluate the performance of the system.
     *
     * @param string $query The query string
     * @param string $answer The generated answer
     * @param array $context The context used to generate the answer
     * @param array $relevantContext The relevant context (ground truth)
     * @return array An array of performance metrics
     */
    public function evaluatePerformance(string $query, string $answer, array $context, array $relevantContext): array;
}
