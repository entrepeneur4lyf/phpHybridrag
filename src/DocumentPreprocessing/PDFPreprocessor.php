<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

use Smalot\PdfParser\Parser;
use Phpml\Tokenization\WhitespaceTokenizer;
use Phpml\FeatureExtraction\TfIdfTransformer;
use HybridRAG\Exception\HybridRAGException;
use HybridRAG\Logging\Logger;

class PDFPreprocessor implements DocumentPreprocessorInterface
{
    private Parser $parser;
    private WhitespaceTokenizer $tokenizer;
    private TfIdfTransformer $tfidfTransformer;

    public function __construct(private Logger $logger)
    {
        $this->parser = new Parser();
        $this->tokenizer = new WhitespaceTokenizer();
        $this->tfidfTransformer = new TfIdfTransformer();
    }

    public function parseDocument(string $filePath): string
    {
        try {
            $this->logger->info("Parsing PDF document", ['filePath' => $filePath]);
            $pdf = $this->parser->parseFile($filePath);
            $text = $pdf->getText();
            $this->logger->info("PDF document parsed successfully", ['filePath' => $filePath]);
            return $text;
        } catch (\Exception $e) {
            $this->logger->error("Failed to parse PDF document", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to parse PDF document: " . $e->getMessage(), 0, $e);
        }
    }

    public function extractMetadata(string $filePath): array
    {
        try {
            $this->logger->info("Extracting metadata from PDF document", ['filePath' => $filePath]);
            $pdf = $this->parser->parseFile($filePath);
            $details = $pdf->getDetails();

            $metadata = [
                'title' => $details['Title'] ?? '',
                'author' => $details['Author'] ?? '',
                'creator' => $details['Creator'] ?? '',
                'creation_date' => $details['CreationDate'] ?? '',
                'modification_date' => $details['ModDate'] ?? '',
                'pages' => $pdf->getPages() ? count($pdf->getPages()) : 0,
            ];

            $this->logger->info("Metadata extracted successfully from PDF document", ['filePath' => $filePath]);
            return $metadata;
        } catch (\Exception $e) {
            $this->logger->error("Failed to extract metadata from PDF document", ['filePath' => $filePath, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to extract metadata from PDF document: " . $e->getMessage(), 0, $e);
        }
    }

    public function chunkText(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        try {
            $this->logger->info("Chunking text", ['chunkSize' => $chunkSize, 'overlap' => $overlap]);
            $tokens = $this->tokenizer->tokenize($text);
            $chunks = [];
            $start = 0;

            while ($start < count($tokens)) {
                $chunk = array_slice($tokens, $start, $chunkSize);
                $chunks[] = implode(' ', $chunk);
                $start += $chunkSize - $overlap;
            }

            $this->logger->info("Text chunked successfully", ['chunkCount' => count($chunks)]);
            return $chunks;
        } catch (\Exception $e) {
            $this->logger->error("Failed to chunk text", ['error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to chunk text: " . $e->getMessage(), 0, $e);
        }
    }
}