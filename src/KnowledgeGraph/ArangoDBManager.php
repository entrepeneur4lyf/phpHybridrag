<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

use ArangoDBClient\Connection;
use ArangoDBClient\ConnectionOptions;
use ArangoDBClient\DocumentHandler;
use ArangoDBClient\EdgeHandler;
use ArangoDBClient\GraphHandler;
use ArangoDBClient\CollectionHandler;
use ArangoDBClient\IndexHandler;
use ArangoDBClient\Export;
use ArangoDBClient\Statement;

/**
 * Class ArangoDBManager
 *
 * This class manages interactions with ArangoDB for the knowledge graph.
 */
class ArangoDBManager extends AbstractGraphDatabase
{
    private Connection $connection;
    private DocumentHandler $documentHandler;
    private EdgeHandler $edgeHandler;
    private GraphHandler $graphHandler;
    private CollectionHandler $collectionHandler;
    private IndexHandler $indexHandler;

    /**
     * Initialize the connection to ArangoDB.
     *
     * @throws HybridRAGException If the connection to ArangoDB fails
     */
    protected function initializeConnection(): void
    {
        try {
            $connectionOptions = [
                ConnectionOptions::OPTION_ENDPOINT => "tcp://{$this->config['arangodb']['host']}:{$this->config['arangodb']['port']}",
                ConnectionOptions::OPTION_AUTH_TYPE => 'Basic',
                ConnectionOptions::OPTION_AUTH_USER => $this->config['arangodb']['username'],
                ConnectionOptions::OPTION_AUTH_PASSWD => $this->config['arangodb']['password'],
                ConnectionOptions::OPTION_DATABASE => $this->config['arangodb']['database'],
            ];

            $this->connection = new Connection($connectionOptions);
            $this->documentHandler = new DocumentHandler($this->connection);
            $this->edgeHandler = new EdgeHandler($this->connection);
            $this->graphHandler = new GraphHandler($this->connection);
            $this->collectionHandler = new CollectionHandler($this->connection);
            $this->indexHandler = new IndexHandler($this->connection);
        } catch (\Exception $e) {
            throw new HybridRAGException("Failed to initialize connection to ArangoDB: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Add a node to the graph database.
     *
     * @param string $collection The collection to add the node to
     * @param array $properties The properties of the node
     * @return string The ID of the newly added node
     */
    public function addNode(string $collection, array $properties): string
    {
        $document = $this->documentHandler->save($collection, $properties);
        return $document->getId();
    }

    /**
     * Add an edge to the graph database.
     *
     * @param string $collection The collection to add the edge to
     * @param string $fromId The ID of the source node
     * @param string $toId The ID of the target node
     * @param array $properties The properties of the edge
     * @return string The ID of the newly added edge
     */
    public function addEdge(string $collection, string $fromId, string $toId, array $properties): string
    {
        $edge = $this->edgeHandler->saveEdge($collection, $fromId, $toId, $properties);
        return $edge->getId();
    }

    /**
     * Retrieve a node from the graph database.
     *
     * @param string $id The ID of the node to retrieve
     * @return array|null The node data, or null if not found
     */
    public function getNode(string $id): ?array
    {
        try {
            $document = $this->documentHandler->get($id);
            return $document->getAll();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Retrieve an edge from the graph database.
     *
     * @param string $id The ID of the edge to retrieve
     * @return array|null The edge data, or null if not found
     */
    public function getEdge(string $id): ?array
    {
        try {
            $edge = $this->edgeHandler->get($id);
            return $edge->getAll();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update a node in the graph database.
     *
     * @param string $id The ID of the node to update
     * @param array $properties The new properties for the node
     */
    public function updateNode(string $id, array $properties): void
    {
        $this->documentHandler->update($id, $properties);
    }

    /**
     * Update an edge in the graph database.
     *
     * @param string $id The ID of the edge to update
     * @param array $properties The new properties for the edge
     */
    public function updateEdge(string $id, array $properties): void
    {
        $this->edgeHandler->update($id, $properties);
    }

    /**
     * Execute a query on the graph database.
     *
     * @param string $query The query to execute
     * @param array $bindVars Variables to bind to the query
     * @return array The query results
     */
    public function query(string $query, array $bindVars = []): array
    {
        $statement = new Statement($this->connection, [
            'query' => $query,
            'bindVars' => $bindVars,
        ]);
        return $statement->execute()->getAll();
    }

    /**
     * Create an index in the graph database.
     *
     * @param string $collection The collection to create the index on
     * @param array $fields The fields to include in the index
     * @param string $type The type of index to create
     * @param bool $unique Whether the index should enforce uniqueness
     */
    public function createIndex(string $collection, array $fields, string $type, bool $unique): void
    {
        $this->indexHandler->createIndex($collection, [
            'type' => $type,
            'fields' => $fields,
            'unique' => $unique,
        ]);
    }

    /**
     * Backup the graph database.
     *
     * @param string $path The path to store the backup
     */
    public function backup(string $path): void
    {
        $export = new Export($this->connection, [
            'restrictType' => Export::RESTRICT_INCLUDE,
            'collections' => [],  // Empty array means all collections
        ]);

        $cursor = $export->execute();
        $fp = fopen($path, 'w');

        while ($doc = $cursor->current()) {
            fwrite($fp, json_encode($doc) . "\n");
            $cursor->next();
        }

        fclose($fp);
    }

    /**
     * Restore the graph database from a backup.
     *
     * @param string $path The path to the backup file
     */
    public function restore(string $path): void
    {
        $fp = fopen($path, 'r');

        while (($line = fgets($fp)) !== false) {
            $doc = json_decode($line, true);
            $collectionName = $doc['_collection'];

            if (!$this->collectionHandler->has($collectionName)) {
                $this->collectionHandler->create($collectionName);
            }

            $this->documentHandler->insert($collectionName, $doc);
        }

        fclose($fp);
    }

    /**
     * Optimize the graph database.
     */
    public function optimize(): void
    {
        $collections = $this->collectionHandler->getAllCollections();
        foreach ($collections as $collection) {
            $this->collectionHandler->compact($collection->getName());
        }
    }
}
