<?php

declare(strict_types=1);

namespace HybridRAG\HybridRAG;

use HybridRAG\VectorRAG\VectorRAG;
use HybridRAG\GraphRAG\GraphRAG;
use HybridRAG\Reranker\RerankerInterface;
use HybridRAG\LanguageModel\LanguageModelInterface;
use HybridRAG\Config\Configuration;

/**
 * Class HybridRAGFactory
 *
 * Factory class for creating HybridRAG instances.
 */
class HybridRAGFactory
{
    /**
     * Create a HybridRAG instance.
     *
     * @param VectorRAG $vectorRAG The VectorRAG instance
     * @param GraphRAG $graphRAG The GraphRAG instance
     * @param RerankerInterface $reranker The reranker instance
     * @param LanguageModelInterface $languageModel The language model instance
     * @param string $configPath The path to the configuration file
     * @return HybridRAG The created HybridRAG instance
     */
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
