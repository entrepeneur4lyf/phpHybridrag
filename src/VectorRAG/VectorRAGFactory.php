<?php

declare(strict_types=1);

namespace HybridRAG\VectorRAG;

use HybridRAG\VectorDatabase\ChromaDBAdapter;
use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\LanguageModel\LanguageModelInterface;
use HybridRAG\Logging\Logger;
use HybridRAG\Config\Configuration;

class VectorRAGFactory
{
    public static function create(
        Configuration $config,
        EmbeddingInterface $embedding,
        LanguageModelInterface $languageModel,
        Logger $logger
    ): VectorRAG {
        $vectorDB = new ChromaDBAdapter($config->get('chromadb'), $logger);
        return new VectorRAG($vectorDB, $embedding, $languageModel, $logger);
    }
}