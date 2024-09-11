<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

interface DocumentPreprocessorInterface
{
    public function parseDocument(string $filePath): string;
    public function extractMetadata(string $filePath): array;
    public function chunkText(string $text, int $chunkSize = 1000, int $overlap = 200): array;
}