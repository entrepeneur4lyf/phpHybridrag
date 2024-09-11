<?php

declare(strict_types=1);

namespace HybridRAG\HybridRAG;

use Phpml\Classification\SVC;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\Clustering\DBSCAN;

/**
 * Class ImprovedNERClassifier
 *
 * This class extends the NERClassifier with improved entity extraction using DBSCAN clustering.
 */
class ImprovedNERClassifier extends NERClassifier
{
    private DBSCAN $dbscan;

    /**
     * ImprovedNERClassifier constructor.
     *
     * Initializes the parent NERClassifier and sets up DBSCAN clustering.
     */
    public function __construct()
    {
        parent::__construct();
        $this->dbscan = new DBSCAN($epsilon = 0.5, $minSamples = 3);
    }

    /**
     * Extract entities from the given text using NER and clustering.
     *
     * @param string $text The input text to extract entities from
     * @return array An array of extracted entities
     */
    public function extractEntities(string $text): array
    {
        $tokens = $this->tokenizer->tokenize($text);
        $predictions = $this->predict($tokens);
        
        $entityClusters = $this->clusterEntities($tokens, $predictions);
        
        return $this->mergeEntityClusters($entityClusters);
    }

    /**
     * Cluster entities based on their embeddings.
     *
     * @param array $tokens An array of tokens
     * @param array $predictions An array of predictions for each token
     * @return array Clustered entities
     */
    private function clusterEntities(array $tokens, array $predictions): array
    {
        $entities = [];
        foreach ($tokens as $i => $token) {
            if ($predictions[$i] === 'ENTITY') {
                $entities[] = [$i, $this->embedding->embed($token)];
            }
        }
        
        return $this->dbscan->cluster($entities);
    }

    /**
     * Merge entity clusters into single entities.
     *
     * @param array $entityClusters An array of entity clusters
     * @return array An array of merged entities
     */
    private function mergeEntityClusters(array $entityClusters): array
    {
        $mergedEntities = [];
        foreach ($entityClusters as $cluster) {
            $mergedEntities[] = implode(' ', array_map(fn($item) => $item[0], $cluster));
        }
        return $mergedEntities;
    }
}
