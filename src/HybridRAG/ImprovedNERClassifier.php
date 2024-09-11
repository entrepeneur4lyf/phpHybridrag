<?php

declare(strict_types=1);

namespace HybridRAG\HybridRAG;

use Phpml\Classification\SVC;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\Clustering\DBSCAN;

class ImprovedNERClassifier extends NERClassifier
{
    private DBSCAN $dbscan;

    public function __construct()
    {
        parent::__construct();
        $this->dbscan = new DBSCAN($epsilon = 0.5, $minSamples = 3);
    }

    public function extractEntities(string $text): array
    {
        $tokens = $this->tokenizer->tokenize($text);
        $predictions = $this->predict($tokens);
        
        $entityClusters = $this->clusterEntities($tokens, $predictions);
        
        return $this->mergeEntityClusters($entityClusters);
    }

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

    private function mergeEntityClusters(array $entityClusters): array
    {
        $mergedEntities = [];
        foreach ($entityClusters as $cluster) {
            $mergedEntities[] = implode(' ', array_map(fn($item) => $item[0], $cluster));
        }
        return $mergedEntities;
    }
}