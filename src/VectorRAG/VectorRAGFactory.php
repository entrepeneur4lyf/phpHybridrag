<?php

declare(strict_types=1);

namespace HybridRAG\VectorRAG;

use HybridRAG\VectorDatabase\ChromaDBAdapter;
use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\LanguageModel\LanguageModelInterface;
use HybridRAG\Logging\Logger;
use HybridRAG\Config\Configuration;

/**
 * Class VectorRAGFactory
 *
 * Factory class for creating VectorRAG instances.
 */
class VectorRAGFactory
{
    /**
     * Create a VectorRAG instance.
     *
     * @param Configuration $config The configuration object
     * @param EmbeddingInterface $embedding The embedding interface
     * @param LanguageModelInterface $languageModel The language model interface
     * @param Logger $logger The logger instance
     * @return VectorRAG The created VectorRAG instance
     */
    public static function create(
        Configuration $config,
        EmbeddingInterface $embedding,
        LanguageModelInterface $languageModel,
        Logger $logger
    ): VectorRAG {
        $vectorDB = new ChromaDBAdapter($config->chromadb, $logger);
        return new VectorRAG($vectorDB, $embedding, $languageModel, $logger);
    }
}
