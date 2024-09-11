<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

class GraphDatabaseFactory
{
    public static function create(string $type, array $config): GraphDatabaseInterface
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