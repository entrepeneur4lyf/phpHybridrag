<?php

declare(strict_types=1);

namespace HybridRAG\HybridRAG;

use HybridRAG\VectorRAG\VectorRAG;
use HybridRAG\GraphRAG\GraphRAG;
use HybridRAG\Reranker\RerankerInterface;
use HybridRAG\LanguageModel\LanguageModelInterface;
use HybridRAG\Config\Configuration;

class HybridRAGFactory
{
    public static function create(
        VectorRAG $vectorRAG,
        GraphRAG $graphRAG,
        RerankerInterface $reranker,
        LanguageModelInterface $languageModel,
        string $configPath
    ): HybridRAG {
        $config = new Configuration($configPath);
        return new HybridRAG($vectorRAG, $graphRAG, $reranker, $languageModel, $config);
    }
}