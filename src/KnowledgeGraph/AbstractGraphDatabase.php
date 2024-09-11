<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

use HybridRAG\Configuration;

/**
 * Abstract class AbstractGraphDatabase
 *
 * This class provides a base implementation for graph databases.
 */
abstract class AbstractGraphDatabase implements GraphDatabaseInterface
{
    /**
     * @var Configuration The configuration for the graph database
     */
    protected Configuration $config;

    /**
     * Connect to the graph database.
     *
     * @param Configuration $config Configuration parameters for the connection
     */
    public function connect(Configuration $config): void
    {
        $this->config = $config;
        $this->initializeConnection();
    }

    /**
     * Initialize the connection to the graph database.
     * This method should be implemented by concrete classes.
     */
    abstract protected function initializeConnection(): void;
}
