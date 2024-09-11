<?php

declare(strict_types=1);

namespace HybridRAG\TextEmbedding;

interface EmbeddingInterface
{
    public function embed(string $text): array;
    public function embedBatch(array $texts): array;
}