<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

use Smalot\PdfParser\Parser;
use Phpml\Tokenization\WhitespaceTokenizer;
use Phpml\Tokenization\NGramTokenizer;
use Phpml\FeatureExtraction\TfIdfTransformer;
use HybridRAG\Exception\HybridRAGException;
use HybridRAG\Logging\Logger;

/**
 * Class PDFPreprocessor
 *
 * This class is responsible for preprocessing PDF documents using N-grams and TF-IDF.
 */
class PDFPreprocessor implements DocumentPreprocessorInterface
{
    private Logger $logger;
    private Parser $parser;
    private WhitespaceTokenizer $tokenizer;
    private NGramTokenizer $nGramTokenizer;
    private TfIdfTransformer $tfidfTransformer;

    /**
     * PDFPreprocessor constructor.
     * @param Logger $logger The logger instance
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->parser = new Parser();
        $this->tokenizer = new WhitespaceTokenizer();
        $this->nGramTokenizer = new NGramTokenizer(1, 3);
        $this->tfidfTransformer = new TfIdfTransformer();
    }

    /**
     * Parse the PDF document and extract its text content.
     *
     * @param string $filePath The path to the PDF file
     * @return string The extracted text from the PDF
     * @throws HybridRAGException If parsing fails
     */
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

    /**
     * Extract metadata from the PDF document.
     *
     * @param string $filePath The path to the PDF file
     * @return array The extracted metadata
     * @throws HybridRAGException If metadata extraction fails
     */
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

    /**
     * Chunk the text into smaller segments using N-grams and TF-IDF.
     *
     * @param string $text The text to chunk
     * @param int $chunkSize The size of each chunk
     * @param int $overlap The overlap between chunks
     * @return array An array of text chunks
     */
    public function chunkText(string $text, int $chunkSize = 1000, int $overlap = 200): array
    {
        $tokens = $this->nGramTokenizer->tokenize($text);
        $tfidf = $this->tfidfTransformer->fit($tokens)->transform($tokens);
        
        $chunks = [];
        $start = 0;
        while ($start < count($tokens)) {
            $chunk = array_slice($tokens, $start, $chunkSize);
            $chunkTfidf = array_slice($tfidf[0], $start, $chunkSize);
            $chunks[] = $this->reconstructChunk($chunk, $chunkTfidf);
            $start += $chunkSize - $overlap;
        }

        return $chunks;
    }

    /**
     * Reconstruct a chunk of text from tokens and their TF-IDF scores.
     *
     * @param array $tokens The tokens in the chunk
     * @param array $tfidf The TF-IDF scores for the tokens
     * @return string The reconstructed chunk
     */
    private function reconstructChunk(array $tokens, array $tfidf): string
    {
        arsort($tfidf);
        $topTokens = array_slice(array_keys($tfidf), 0, 100);
        return implode(' ', array_intersect($tokens, $topTokens));
    }
}