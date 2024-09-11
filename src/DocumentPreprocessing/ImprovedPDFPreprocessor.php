<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

use Smalot\PdfParser\Parser;
use Phpml\Tokenization\NGramTokenizer;
use Phpml\FeatureExtraction\TfIdfTransformer;

/**
 * Class ImprovedPDFPreprocessor
 *
 * This class extends the PDFPreprocessor with improved text chunking using N-grams and TF-IDF.
 */
class ImprovedPDFPreprocessor extends PDFPreprocessor
{
    private NGramTokenizer $nGramTokenizer;
    private TfIdfTransformer $tfidfTransformer;

    /**
     * ImprovedPDFPreprocessor constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->nGramTokenizer = new NGramTokenizer(1, 3);
        $this->tfidfTransformer = new TfIdfTransformer();
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
