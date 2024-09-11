<?php

declare(strict_types=1);

namespace HybridRAG\LanguageModel;

use HybridRAG\Logging\Logger;
use HybridRAG\Config\Configuration;

/**
 * Class OpenAILanguageModelFactory
 *
 * Factory class for creating OpenAILanguageModel instances.
 */
class OpenAILanguageModelFactory
{
    /**
     * Create an OpenAILanguageModel instance.
     *
     * @param Configuration $config The configuration object
     * @param Logger $logger The logger instance
     * @return OpenAILanguageModel The created OpenAILanguageModel instance
     */
    public static function create(
        Configuration $config,
        Logger $logger
    ): OpenAILanguageModel {
        $apiKey = $config->get('openai.api_key');
        $model = $config->get('openai.language_model');
        return new OpenAILanguageModel($apiKey, $model, $logger);
    }
}
