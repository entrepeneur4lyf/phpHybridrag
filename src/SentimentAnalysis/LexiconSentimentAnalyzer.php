<?php

declare(strict_types=1);

namespace HybridRAG\SentimentAnalysis;

use Phpml\Tokenization\WordTokenizer;

/**
 * Class LexiconSentimentAnalyzer
 *
 * This class provides sentiment analysis functionality using a lexicon-based approach.
 */
class LexiconSentimentAnalyzer
{
    private array $lexicon;
    private WordTokenizer $tokenizer;

    /**
     * LexiconSentimentAnalyzer constructor.
     *
     * @param string $lexiconPath The path to the sentiment lexicon file
     */
    public function __construct(string $lexiconPath)
    {
        $this->lexicon = $this->loadLexicon($lexiconPath);
        $this->tokenizer = new WordTokenizer();
    }

    /**
     * Analyze the sentiment of the given text.
     *
     * @param string $text The text to analyze
     * @return float The sentiment score (positive values indicate positive sentiment, negative values indicate negative sentiment)
     */
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

    /**
     * Load the sentiment lexicon from a file.
     *
     * @param string $path The path to the lexicon file
     * @return array The loaded lexicon as an associative array
     */
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
