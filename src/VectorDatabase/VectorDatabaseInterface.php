<?php

declare(strict_types=1);

namespace HybridRAG\VectorDatabase;

interface VectorDatabaseInterface
{
    public function insert(string $id, array $vector, array $metadata = []): void;
    public function query(array $vector, int $topK = 5, array $filters = []): array;
    public function update(string $id, array $vector, array $metadata = []): void;
    public function delete(string $id): void;
    public function getAllDocuments(): array; // New method
}