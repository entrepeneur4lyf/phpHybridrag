<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

interface GraphDatabaseInterface
{
    public function connect(array $config): void;
    public function addNode(string $collection, array $properties): string;
    public function addEdge(string $collection, string $fromId, string $toId, array $properties): string;
    public function getNode(string $id): ?array;
    public function getEdge(string $id): ?array;
    public function updateNode(string $id, array $properties): void;
    public function updateEdge(string $id, array $properties): void;
    public function query(string $query, array $bindVars = []): array;
    public function createIndex(string $collection, array $fields, string $type, bool $unique): void;
    public function backup(string $path): void;
    public function restore(string $path): void;
    public function optimize(): void;
}