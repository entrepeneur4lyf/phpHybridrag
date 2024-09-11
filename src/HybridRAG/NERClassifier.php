<?php

declare(strict_types=1);

namespace HybridRAG\HybridRAG;

use Phpml\Classification\SVC;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\Clustering\DBSCAN;

/**
 * Class NERClassifier
 *
 * This class implements an improved Named Entity Recognition (NER) classifier
 * using Support Vector Classification (SVC) and DBSCAN clustering.
 */
class NERClassifier implements \Phpml\Classification\Classifier
{
    private SVC $classifier;
    private TokenCountVectorizer $vectorizer;
    private DBSCAN $dbscan;
    private WordTokenizer $tokenizer;

    /**
     * NERClassifier constructor.
     *
     * Initializes the SVC classifier, TokenCountVectorizer, and DBSCAN clustering.
     */
    public function __construct()
    {
        $this->classifier = new SVC(Kernel::RBF, 1.0, 3);
        $this->vectorizer = new TokenCountVectorizer(new WordTokenizer());
        $this->dbscan = new DBSCAN($epsilon = 0.5, $minSamples = 3);
        $this->tokenizer = new WordTokenizer();
    }

    /**
     * Train the NER classifier with the given samples and labels.
     *
     * @param array $samples An array of text samples
     * @param array $labels An array of corresponding labels
     */
    public function train(array $samples, array $labels): void
    {
        $features = $this->vectorizer->fit($samples)->transform($samples);
        $this->classifier->train($features, $labels);
    }

    /**
     * Predict the labels for the given samples.
     *
     * @param array $samples An array of text samples to predict
     * @return array An array of predicted labels
     */
    public function predict(array $samples): array
    {
        $features = $this->vectorizer->transform($samples);
        return $this->classifier->predict($features);
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
     * Cluster entities based on their positions in the text.
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
                $entities[] = [$i, [$i]]; // Using token position as a simple embedding
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
            $clusterTokens = [];
            foreach ($cluster as $item) {
                $clusterTokens[$item[0]] = $item[0];
            }
            ksort($clusterTokens);
            $mergedEntities[] = implode(' ', array_map(fn($index) => $this->tokenizer->getTokens()[$index], $clusterTokens));
        }
        return $mergedEntities;
    }
}
