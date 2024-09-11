<?php

declare(strict_types=1);

namespace HybridRAG\Evaluation;

use HybridRAG\LanguageModel\LanguageModelInterface;

/**
 * Class EvaluationMetrics
 *
 * This class provides methods to evaluate the performance of the HybridRAG system.
 */
class EvaluationMetrics
{
    /**
     * EvaluationMetrics constructor.
     *
     * @param LanguageModelInterface $languageModel The language model to use for evaluations
     */
    public function __construct(private LanguageModelInterface $languageModel)
    {}

    /**
     * Calculate the faithfulness of an answer to the given context.
     *
     * @param string $answer The generated answer
     * @param array $context The context used to generate the answer
     * @return float The faithfulness score (0 to 1)
     */
    public function calculateFaithfulness(string $answer, array $context): float
    {
        $prompt = "Given the following context and answer, rate the faithfulness of the answer to the context on a scale of 0 to 1, where 0 is completely unfaithful and 1 is perfectly faithful. Only respond with a number between 0 and 1.\n\nContext: " . json_encode($context) . "\n\nAnswer: $answer\n\nFaithfulness score:";
        
        $faithfulnessScore = $this->languageModel->generateResponse($prompt, []);
        return (float) $faithfulnessScore;
    }

    /**
     * Calculate the relevance of an answer to the given query.
     *
     * @param string $query The original query
     * @param string $answer The generated answer
     * @return float The relevance score (0 to 1)
     */
    public function calculateAnswerRelevance(string $query, string $answer): float
    {
        $prompt = "Given the following query and answer, rate the relevance of the answer to the query on a scale of 0 to 1, where 0 is completely irrelevant and 1 is perfectly relevant. Only respond with a number between 0 and 1.\n\nQuery: $query\n\nAnswer: $answer\n\nRelevance score:";
        
        $relevanceScore = $this->languageModel->generateResponse($prompt, []);
        return (float) $relevanceScore;
    }

    /**
     * Calculate the precision of the retrieved context.
     *
     * @param array $retrievedContext The context retrieved by the system
     * @param array $relevantContext The actually relevant context
     * @return float The precision score
     */
    public function calculateContextPrecision(array $retrievedContext, array $relevantContext): float
    {
        $relevantRetrieved = array_intersect($retrievedContext, $relevantContext);
        return count($relevantRetrieved) / count($retrievedContext);
    }

    /**
     * Calculate the recall of the retrieved context.
     *
     * @param array $retrievedContext The context retrieved by the system
     * @param array $relevantContext The actually relevant context
     * @return float The recall score
     */
    public function calculateContextRecall(array $retrievedContext, array $relevantContext): float
    {
        $relevantRetrieved = array_intersect($retrievedContext, $relevantContext);
        return count($relevantRetrieved) / count($relevantContext);
    }

    /**
     * Generate a comprehensive evaluation report.
     *
     * @param string $query The original query
     * @param string $answer The generated answer
     * @param array $context The context used to generate the answer
     * @param array $relevantContext The actually relevant context
     * @return array The evaluation report
     */
    public function generateEvaluationReport(string $query, string $answer, array $context, array $relevantContext): array
    {
        $faithfulness = $this->calculateFaithfulness($answer, $context);
        $relevance = $this->calculateAnswerRelevance($query, $answer);
        $precision = $this->calculateContextPrecision($context, $relevantContext);
        $recall = $this->calculateContextRecall($context, $relevantContext);
        $f1Score = 2 * ($precision * $recall) / ($precision + $recall);

        return [
            'faithfulness' => $faithfulness,
            'relevance' => $relevance,
            'context_precision' => $precision,
            'context_recall' => $recall,
            'f1_score' => $f1Score,
        ];
    }
}
