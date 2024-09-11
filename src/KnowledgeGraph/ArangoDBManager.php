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

class ArangoDBManager extends AbstractGraphDatabase
{
    private Connection $connection;
    private DocumentHandler $documentHandler;
    private EdgeHandler $edgeHandler;
    private GraphHandler $graphHandler;
    private CollectionHandler $collectionHandler;
    private IndexHandler $indexHandler;

    protected function initializeConnection(): void
    {
        $connectionOptions = [
            ConnectionOptions::OPTION_ENDPOINT => "tcp://{$this->config['host']}:{$this->config['port']}",
            ConnectionOptions::OPTION_AUTH_TYPE => 'Basic',
            ConnectionOptions::OPTION_AUTH_USER => $this->config['username'],
            ConnectionOptions::OPTION_AUTH_PASSWD => $this->config['password'],
            ConnectionOptions::OPTION_DATABASE => $this->config['database'],
        ];

        $this->connection = new Connection($connectionOptions);
        $this->documentHandler = new DocumentHandler($this->connection);
        $this->edgeHandler = new EdgeHandler($this->connection);
        $this->graphHandler = new GraphHandler($this->connection);
        $this->collectionHandler = new CollectionHandler($this->connection);
        $this->indexHandler = new IndexHandler($this->connection);
    }

    public function addNode(string $collection, array $properties): string
    {
        $document = $this->documentHandler->save($collection, $properties);
        return $document->getId();
    }

    public function addEdge(string $collection, string $fromId, string $toId, array $properties): string
    {
        $edge = $this->edgeHandler->saveEdge($collection, $fromId, $toId, $properties);
        return $edge->getId();
    }

    public function getNode(string $id): ?array
    {
        try {
            $document = $this->documentHandler->get($id);
            return $document->getAll();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getEdge(string $id): ?array
    {
        try {
            $edge = $this->edgeHandler->get($id);
            return $edge->getAll();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function updateNode(string $id, array $properties): void
    {
        $this->documentHandler->update($id, $properties);
    }

    public function updateEdge(string $id, array $properties): void
    {
        $this->edgeHandler->update($id, $properties);
    }

    public function query(string $query, array $bindVars = []): array
    {
        $statement = new Statement($this->connection, [
            'query' => $query,
            'bindVars' => $bindVars,
        ]);
        return $statement->execute()->getAll();
    }

    public function createIndex(string $collection, array $fields, string $type, bool $unique): void
    {
        $this->indexHandler->createIndex($collection, [
            'type' => $type,
            'fields' => $fields,
            'unique' => $unique,
        ]);
    }

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

    public function optimize(): void
    {
        $collections = $this->collectionHandler->getAllCollections();
        foreach ($collections as $collection) {
            $this->collectionHandler->compact($collection->getName());
        }
    }
}