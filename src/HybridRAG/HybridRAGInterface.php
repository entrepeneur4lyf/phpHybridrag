<?php

declare(strict_types=1);

namespace HybridRAG\HybridRAG;

interface HybridRAGInterface
{
    public function addDocument(string $id, string $content, array $metadata = []): void;
    public function retrieveContext(string $query): array;
    public function generateAnswer(string $query, array $context): string;
    public function improveModel(array $unlabeledSamples, int $numSamples): array;
    public function evaluatePerformance(string $query, string $answer, array $context, array $relevantContext): array;
}