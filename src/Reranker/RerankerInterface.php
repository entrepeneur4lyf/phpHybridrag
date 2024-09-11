<?php

declare(strict_types=1);

namespace HybridRAG\Reranker;

interface RerankerInterface
{
    public function rerank(string $query, array $results, int $topK): array;
}