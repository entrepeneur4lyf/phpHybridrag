<?php

declare(strict_types=1);

namespace HybridRAG\SentimentAnalysis;

use Phpml\Tokenization\WordTokenizer;

class LexiconSentimentAnalyzer
{
    private array $lexicon;
    private WordTokenizer $tokenizer;

    public function __construct(string $lexiconPath)
    {
        $this->lexicon = $this->loadLexicon($lexiconPath);
        $this->tokenizer = new WordTokenizer();
    }

    public function analyzeSentiment(string $text): float
    {
        $tokens = $this->tokenizer->tokenize(strtolower($text));
        $sentimentScore = 0;

        foreach ($tokens as $token) {
            if (isset($this->lexicon[$token])) {
                $sentimentScore += $this->lexicon[$token];
            }
        }

        return $sentimentScore / count($tokens);
    }

    private function loadLexicon(string $path): array
    {
        $lexicon = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            list($word, $score) = explode("\t", $line);
            $lexicon[$word] = (float) $score;
        }
        return $lexicon;
    }
}