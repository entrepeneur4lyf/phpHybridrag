<?php

declare(strict_types=1);

namespace HybridRAG\TextEmbedding;

/**
 * Interface EmbeddingInterface
 *
 * Defines the contract for text embedding implementations in the HybridRAG system.
 */
interface EmbeddingInterface
{
    /**
     * Embed a single text into a vector representation.
     *
     * @param string $text The text to embed
     * @return array The vector representation of the text
     */
    public function embed(string $text): array;

    /**
     * Embed multiple texts into vector representations.
     *
     * @param array $texts An array of texts to embed
     * @return array An array of vector representations for the input texts
     */
    public function embedBatch(array $texts): array;
}
