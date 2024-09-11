<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

use Psr\SimpleCache\CacheInterface;
use HybridRAG\Logging\Logger;

/**
 * Class KnowledgeGraphBuilderFactory
 *
 * Factory class for creating KnowledgeGraphBuilder instances.
 */
class KnowledgeGraphBuilderFactory
{
    /**
     * Create a KnowledgeGraphBuilder instance.
     *
     * @param GraphDatabaseInterface $dbManager The graph database manager
     * @param CacheInterface $cache The cache interface
     * @param Logger $logger The logger
     * @return KnowledgeGraphBuilder The created KnowledgeGraphBuilder instance
     */
    public static function create(
        GraphDatabaseInterface $dbManager,
        CacheInterface $cache,
        Logger $logger
    ): KnowledgeGraphBuilder {
        return new KnowledgeGraphBuilder($dbManager, $cache, $logger);
    }
}
