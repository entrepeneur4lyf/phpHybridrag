<?php

declare(strict_types=1);

namespace HybridRAG\Evaluation;

use Phpml\Metric\Accuracy;
use Phpml\Metric\ConfusionMatrix;

/**
 * Class RAGEvaluator
 *
 * This class provides methods to evaluate the performance of the Retrieval-Augmented Generation (RAG) system.
 */
class RAGEvaluator
{
    /**
     * Evaluate the accuracy of the retrieval process.
     *
     * @param array $queries The input queries
     * @param array $expectedResults The expected retrieval results
     * @param array $actualResults The actual retrieval results
     * @return float The accuracy score
     */
    public function evaluateRetrievalAccuracy(array $queries, array $expectedResults, array $actualResults): float
    {
        $expected = [];
        $actual = [];

        foreach ($queries as $i => $query) {
            $expected[] = $expectedResults[$i];
            $actual[] = $actualResults[$i];
        }

        return Accuracy::score($expected, $actual);
    }

    /**
     * Evaluate the relevance of generated answers.
     *
     * @param array $groundTruth The ground truth relevance labels
     * @param array $generatedAnswers The generated answers
     * @return array An array containing precision, recall, and F1 score
     */
    public function evaluateAnswerRelevance(array $groundTruth, array $generatedAnswers): array
    {
        // Assuming binary relevance: 1 for relevant, 0 for not relevant
        $confusionMatrix = ConfusionMatrix::compute($groundTruth, $generatedAnswers);

        $precision = $confusionMatrix[1][1] / ($confusionMatrix[1][1] + $confusionMatrix[0][1]);
        $recall = $confusionMatrix[1][1] / ($confusionMatrix[1][1] + $confusionMatrix[1][0]);
        $f1Score = 2 * ($precision * $recall) / ($precision + $recall);

        return [
            'precision' => $precision,
            'recall' => $recall,
            'f1_score' => $f1Score
        ];
    }
}
