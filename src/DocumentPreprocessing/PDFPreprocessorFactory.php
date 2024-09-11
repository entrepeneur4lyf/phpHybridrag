<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

use HybridRAG\Logging\Logger;

class PDFPreprocessorFactory
{
    public static function create(Logger $logger): PDFPreprocessor
    {
        return new PDFPreprocessor($logger);
    }
}