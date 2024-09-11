<?php

declare(strict_types=1);

namespace HybridRAG\Reranker;

/**
 * Interface RerankerInterface
 *
 * Defines the contract for reranker implementations in the HybridRAG system.
 */
interface RerankerInterface
{
    /**
     * Rerank the given results based on the query.
     *
     * @param string $query The query string
     * @param array $results The initial results to rerank
     * @param int $topK The number of top results to return
     * @return array The reranked results
     */
    public function rerank(string $query, array $results, int $topK): array;
}
