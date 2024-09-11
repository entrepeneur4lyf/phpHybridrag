<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

/**
 * Interface DocumentPreprocessorInterface
 *
 * This interface defines the contract for document preprocessors in the HybridRAG system.
 */
interface DocumentPreprocessorInterface
{
    /**
     * Parse the document and extract its content.
     *
     * @param string $filePath The path to the document file
     * @return string The extracted content from the document
     */
    public function parseDocument(string $filePath): string;

    /**
     * Extract metadata from the document.
     *
     * @param string $filePath The path to the document file
     * @return array The extracted metadata
     */
    public function extractMetadata(string $filePath): array;

    /**
     * Chunk the text into smaller segments.
     *
     * @param string $text The text to chunk
     * @param int $chunkSize The size of each chunk
     * @param int $overlap The overlap between chunks
     * @return array An array of text chunks
     */
    public function chunkText(string $text, int $chunkSize = 1000, int $overlap = 200): array;
}
