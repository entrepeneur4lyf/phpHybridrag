<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

use HybridRAG\Logging\Logger;

/**
 * Class PDFPreprocessorFactory
 *
 * This class is responsible for creating PDFPreprocessor instances.
 */
class PDFPreprocessorFactory
{
    /**
     * Create a PDFPreprocessor instance.
     *
     * @param Logger $logger The logger instance to be used by the PDFPreprocessor
     * @return PDFPreprocessor The created PDFPreprocessor instance
     */
    public static function create(Logger $logger): PDFPreprocessor
    {
        return new PDFPreprocessor($logger);
    }
}
