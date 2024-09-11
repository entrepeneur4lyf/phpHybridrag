<?php

declare(strict_types=1);

namespace HybridRAG\VectorRAG;

interface VectorRAGInterface
{
    public function addDocument(string $id, string $content, array $metadata = []): void;
    public function retrieveContext(string $query, int $topK = 5, array $filters = []): array;
    public function generateAnswer(string $query, array $context): string;
}