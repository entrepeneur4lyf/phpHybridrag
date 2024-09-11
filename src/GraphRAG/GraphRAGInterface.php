<?php

declare(strict_types=1);

namespace HybridRAG\GraphRAG;

interface GraphRAGInterface
{
    public function addEntity(string $id, string $content, array $metadata = []): string;
    public function addRelationship(string $fromId, string $toId, string $type, array $attributes = []): string;
    public function retrieveContext(string $query, int $maxDepth = null): array;
    public function generateAnswer(string $query, array $context): string;
}