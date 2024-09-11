<?php

declare(strict_types=1);

namespace HybridRAG\GraphRAG;

use Phpml\Clustering\KMeans;

/**
 * Class ClusteringGraphRAG
 *
 * Extends the GraphRAG class to include entity clustering functionality.
 */
class ClusteringGraphRAG extends GraphRAG
{
    private KMeans $kmeans;

    /**
     * ClusteringGraphRAG constructor.
     *
     * @param mixed ...$args Arguments to pass to the parent constructor
     */
    public function __construct(/* ... */)
    {
        parent::__construct(/* ... */);
        $this->kmeans = new KMeans(5); // Adjust the number of clusters as needed
    }

    /**
     * Cluster entities based on their embeddings.
     *
     * @return array An array of clustered entities
     */
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

    /**
     * Retrieve context for a given query, enhanced with cluster information.
     *
     * @param string $query The query string
     * @param int|null $maxDepth The maximum depth to traverse in the graph (optional)
     * @return array An array of relevant context from the graph, enhanced with cluster information
     */
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

    /**
     * Find the cluster for a given item.
     *
     * @param array $item The item to find the cluster for
     * @param array $clusteredContext The clustered context
     * @return int|null The cluster index, or null if not found
     */
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
