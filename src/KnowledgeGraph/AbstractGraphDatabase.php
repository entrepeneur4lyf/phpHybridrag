<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

/**
 * Abstract class AbstractGraphDatabase
 *
 * This class provides a base implementation for graph databases.
 */
abstract class AbstractGraphDatabase implements GraphDatabaseInterface
{
    /**
     * @var array The configuration for the graph database
     */
    protected array $config;

    /**
     * Connect to the graph database.
     *
     * @param array $config Configuration parameters for the connection
     */
    public function connect(array $config): void
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
