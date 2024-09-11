<?php

declare(strict_types=1);

namespace HybridRAG\DocumentPreprocessing;

use Smalot\PdfParser\Parser;
use Phpml\Tokenization\NGramTokenizer;
use Phpml\FeatureExtraction\TfIdfTransformer;

class ImprovedPDFPreprocessor extends PDFPreprocessor
{
    private NGramTokenizer $nGramTokenizer;
    private TfIdfTransformer $tfidfTransformer;

    public function __construct()
    {
        parent::__construct();
        $this->nGramTokenizer = new NGramTokenizer(1, 3);
        $this->tfidfTransformer = new TfIdfTransformer();
    }

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

    private function reconstructChunk(array $tokens, array $tfidf): string
    {
        arsort($tfidf);
        $topTokens = array_slice(array_keys($tfidf), 0, 100);
        return implode(' ', array_intersect($tokens, $topTokens));
    }
}