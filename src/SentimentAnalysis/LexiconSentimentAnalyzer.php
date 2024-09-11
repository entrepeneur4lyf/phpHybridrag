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
     * @param string $path The path to the lexicon file
     * @return array The loaded lexicon as an associative array
     */
    private function loadLexicon(string $path): bool
    {
        try {
            $lexicon = [];
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                list($word, $score) = explode("\t", $line);
                $lexicon[$word] = (float) $score;
            }

            // Compare the keys of the loaded lexicon to the default keys
            $defaultKeys = array_keys($this->lexicon);
            $loadedKeys = array_keys($lexicon);
            if (array_diff($defaultKeys, $loadedKeys) || array_diff($loadedKeys, $defaultKeys)) {
                return false;
            }

            // Set the lexicon to the loaded lexicon
            $this->lexicon = $lexicon;
            return true;
        } catch (\Exception $e) {
            // Handle any exceptions that occur during file operations
            return false;
        }
    }

    /**
     * Set the sentiment lexicon.
     *
     * @param array $lexicon An associative array of words and their sentiment scores
     * @return bool True if the lexicon was successfully set, false otherwise
     */
    public function setLexicon(array $lexicon): bool
    {
        try {
            // Compare the keys of the provided lexicon to the default keys
            $defaultKeys = array_keys($this->lexicon);
            $providedKeys = array_keys($lexicon);

            // Check if the provided keys are a subset of the default keys
            if (array_diff($providedKeys, $defaultKeys)) {
                return false;
            }

            // Merge the provided lexicon into the default lexicon
            $this->lexicon = array_merge($this->lexicon, $lexicon);
            return true;
        } catch (\Exception $e) {
            // Handle any exceptions that occur during the process
            return false;
        }
    }
}