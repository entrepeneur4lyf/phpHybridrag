<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

use Psr\SimpleCache\CacheInterface;
use HybridRAG\Logging\Logger;

class KnowledgeGraphBuilderFactory
{
    public static function create(
        GraphDatabaseInterface $dbManager,
        CacheInterface $cache,
        Logger $logger
    ): KnowledgeGraphBuilder {
        return new KnowledgeGraphBuilder($dbManager, $cache, $logger);
    }
}