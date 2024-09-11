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
    private WordTokenizer $tokenizer;

    private array $lexicon = [
        "happy" => 1.0,
        "sad" => -1.0,
        "neutral" => 0.0,
        "excited" => 1.5,
        "angry" => -1.5
    ];

    /**
     * LexiconSentimentAnalyzer constructor.
     *
     * @param string $lexiconPath The path to the sentiment lexicon file
     */
    public function __construct()
    {
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
     * @param array $lexicon The lexicon to set
     * @return array The loaded lexicon as an associative array where keys are words and values are sentiment scores
     */
    private function setLexicon(array $lexicon): array
    {
        $defaultKeys = array_keys($this->lexicon);
        $passedKeys = array_keys($lexicon);

        // Check if all default keys are present in the passed lexicon
        foreach ($defaultKeys as $key) {
            if (!in_array($key, $passedKeys)) {
                // Merge the passed lexicon with the default lexicon
                $lexicon = array_merge($this->lexicon, $lexicon);
                break;
            }
        }

        // Validate the values in the passed lexicon
        foreach ($lexicon as $word => $score) {
            if (!is_string($word) || !is_float($score)) {
                throw new \InvalidArgumentException("Invalid lexicon format. Each key must be a string and each value must be a float.");
            }
        }
        return $lexicon;
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
