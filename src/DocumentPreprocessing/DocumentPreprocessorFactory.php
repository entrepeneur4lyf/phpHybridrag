<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

use HybridRAG\Logging\Logger;
use HybridRAG\Configuration\Configuration;

class DocumentPreprocessorFactory
{
    public static function create(string $fileType, Logger $logger, Configuration $config): DocumentPreprocessorInterface
    {
        switch (strtolower($fileType)) {
            case 'pdf':
                return new PDFPreprocessor($logger);
            case 'image':
                return new ImagePreprocessor($logger, $config->get('openai.api_key'));
            case 'audio':
                return new AudioPreprocessor($logger, $config->get('openai.api_key'));
            case 'video':
                $audioPreprocessor = new AudioPreprocessor($logger, $config->get('openai.api_key'));
                $imagePreprocessor = new ImagePreprocessor($logger, $config->get('openai.api_key'));
                return new VideoPreprocessor($logger, $audioPreprocessor, $imagePreprocessor);
            default:
                throw new \InvalidArgumentException("Unsupported file type: $fileType");
        }
    }
}