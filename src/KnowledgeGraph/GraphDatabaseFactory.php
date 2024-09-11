<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

/**
 * Class GraphDatabaseFactory
 *
 * Factory class for creating graph database instances.
 */
class GraphDatabaseFactory
{
    /**
     * Create a graph database instance based on the given type and configuration.
     *
     * @param string $type The type of graph database to create
     * @param Configuration $config The configuration for the database
     * @return GraphDatabaseInterface The created graph database instance
     * @throws \InvalidArgumentException If an unsupported database type is provided
     */
    public static function create(string $type, Configuration $config): GraphDatabaseInterface
    {
        switch ($type) {
            case 'arangodb':
                $db = new ArangoDBManager();
                break;
            // Add cases for other database types here
            default:
                throw new \InvalidArgumentException("Unsupported database type: $type");
        }

        $db->connect($config);
        return $db;
    }
}
