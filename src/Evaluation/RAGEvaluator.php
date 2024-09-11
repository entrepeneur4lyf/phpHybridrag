<?php

declare(strict_types=1);

namespace HybridRAG\Evaluation;

use Phpml\Metric\Accuracy;
use Phpml\Metric\ConfusionMatrix;

class RAGEvaluator
{
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