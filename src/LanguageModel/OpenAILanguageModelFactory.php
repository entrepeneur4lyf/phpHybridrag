<?php

declare(strict_types=1);

namespace HybridRAG\LanguageModel;

use HybridRAG\Logging\Logger;
use HybridRAG\Config\Configuration;

class OpenAILanguageModelFactory
{
    public static function create(
        Configuration $config,
        Logger $logger
    ): OpenAILanguageModel {
        $apiKey = $config->get('openai.api_key');
        $model = $config->get('openai.language_model');
        return new OpenAILanguageModel($apiKey, $model, $logger);
    }
}