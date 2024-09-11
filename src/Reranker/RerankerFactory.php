<?php

declare(strict_types=1);

namespace HybridRAG\Reranker;

use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\VectorDatabase\VectorDatabaseInterface;
use Psr\SimpleCache\CacheInterface;
use HybridRAG\Logging\Logger;

class RerankerFactory
{
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