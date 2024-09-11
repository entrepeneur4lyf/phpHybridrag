<?php

declare(strict_types=1);

namespace HybridRAG\GraphRAG;

use Phpml\Clustering\KMeans;

class ClusteringGraphRAG extends GraphRAG
{
    private KMeans $kmeans;

    public function __construct(/* ... */)
    {
        parent::__construct(/* ... */);
        $this->kmeans = new KMeans(5); // Adjust the number of clusters as needed
    }

    public function clusterEntities(): array
    {
        $entities = $this->kg->getAllEntities();
        $embeddings = array_map(fn($entity) => $entity['embedding'], $entities);
        $clusters = $this->kmeans->cluster($embeddings);

        $clusteredEntities = [];
        foreach ($clusters as $i => $cluster) {
            $clusteredEntities[$i] = array_map(fn($index) => $entities[$index], $cluster);
        }

        return $clusteredEntities;
    }

    public function retrieveContext(string $query, int $maxDepth = null): array
    {
        $context = parent::retrieveContext($query, $maxDepth);
        $clusteredContext = $this->clusterEntities();
        
        // Enhance context with cluster information
        foreach ($context as &$item) {
            $item['cluster'] = $this->findCluster($item, $clusteredContext);
        }

        return $context;
    }

    private function findCluster(array $item, array $clusteredContext): ?int
    {
        foreach ($clusteredContext as $clusterIndex => $cluster) {
            if (in_array($item['id'], array_column($cluster, 'id'))) {
                return $clusterIndex;
            }
        }
        return null;
    }
}