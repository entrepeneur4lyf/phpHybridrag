<?php

declare(strict_types=1);

namespace HybridRAG\Similarity;

/**
 * Class SimilarityCalculator
 *
 * This class provides methods to calculate similarity between vectors and texts.
 */
class SimilarityCalculator
{
    /**
     * Calculate the cosine similarity between two vectors.
     *
     * @param array $vector1 The first vector
     * @param array $vector2 The second vector
     * @return float The cosine similarity between the two vectors
     */
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

    /**
     * Calculate the Jaccard similarity between two texts.
     *
     * @param string $text1 The first text
     * @param string $text2 The second text
     * @return float The Jaccard similarity between the two texts
     */
    public function calculateTextSimilarity(string $text1, string $text2): float
    {
        $set1 = array_flip(explode(' ', strtolower($text1)));
        $set2 = array_flip(explode(' ', strtolower($text2)));
        
        $intersection = count(array_intersect_key($set1, $set2));
        $union = count(array_union_key($set1, $set2));
        
        return $intersection / $union;
    }
}
