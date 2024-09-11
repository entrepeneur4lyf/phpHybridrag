<?php

declare(strict_types=1);

namespace HybridRAG\Reranker;

use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\VectorDatabase\VectorDatabaseInterface;
use Psr\SimpleCache\CacheInterface;
use HybridRAG\Logging\Logger;

/**
 * Class RerankerFactory
 *
 * Factory class for creating reranker instances.
 */
class RerankerFactory
{
    /**
     * Create a reranker instance with the given dependencies and weights.
     *
     * @param EmbeddingInterface $embedding The embedding interface
     * @param VectorDatabaseInterface $vectorDB The vector database interface
     * @param CacheInterface $cache The cache interface
     * @param Logger $logger The logger instance
     * @param float $bm25Weight The weight for BM25 scoring (default: 0.5)
     * @param float $semanticWeight The weight for semantic scoring (default: 0.5)
     * @return RerankerInterface The created reranker instance
     */
    public static function create(
        EmbeddingInterface $embedding,
        VectorDatabaseInterface $vectorDB,
        CacheInterface $cache,
        Logger $logger,
        float $bm25Weight = 0.5,
        float $semanticWeight = 0.5
    ): RerankerInterface {
        return new HybridReranker($embedding, $vectorDB, $cache, $logger, $bm25Weight, $semanticWeight);
    }
}
