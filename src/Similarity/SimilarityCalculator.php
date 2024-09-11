<?php

declare(strict_types=1);

namespace HybridRAG\Similarity;

class SimilarityCalculator
{
    public function calculateVectorSimilarity(array $vector1, array $vector2): float
    {
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        foreach ($vector1 as $i => $value) {
            $dotProduct += $value * $vector2[$i];
            $magnitude1 += $value * $value;
            $magnitude2 += $vector2[$i] * $vector2[$i];
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    public function calculateTextSimilarity(string $text1, string $text2): float
    {
        $set1 = array_flip(explode(' ', strtolower($text1)));
        $set2 = array_flip(explode(' ', strtolower($text2)));
        
        $intersection = count(array_intersect_key($set1, $set2));
        $union = count(array_union_key($set1, $set2));
        
        return $intersection / $union;
    }
}