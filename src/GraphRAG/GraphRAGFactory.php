<?php

declare(strict_types=1);

namespace HybridRAG\GraphRAG;

use HybridRAG\KnowledgeGraph\KnowledgeGraphBuilder;
use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\LanguageModel\LanguageModelInterface;
use HybridRAG\Logging\Logger;

class GraphRAGFactory
{
    public static function create(
        KnowledgeGraphBuilder $kg,
        EmbeddingInterface $embedding,
        LanguageModelInterface $languageModel,
        Logger $logger,
        int $maxDepth = 2,
        float $entitySimilarityThreshold = 0.7
    ): GraphRAG {
        return new GraphRAG($kg, $embedding, $languageModel, $logger, $maxDepth, $entitySimilarityThreshold);
    }
}