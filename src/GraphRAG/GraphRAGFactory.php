<?php

declare(strict_types=1);

namespace HybridRAG\GraphRAG;

use HybridRAG\KnowledgeGraph\KnowledgeGraphBuilder;
use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\LanguageModel\LanguageModelInterface;
use HybridRAG\Logging\Logger;

/**
 * Class GraphRAGFactory
 *
 * Factory class for creating GraphRAG instances.
 */
class GraphRAGFactory
{
    /**
     * Create a GraphRAG instance.
     *
     * @param KnowledgeGraphBuilder $kg The knowledge graph builder
     * @param EmbeddingInterface $embedding The embedding interface
     * @param LanguageModelInterface $languageModel The language model interface
     * @param Logger $logger The logger instance
     * @param int $maxDepth The maximum depth for graph traversal (default: 2)
     * @param float $entitySimilarityThreshold The threshold for entity similarity (default: 0.7)
     * @return GraphRAG The created GraphRAG instance
     */
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
