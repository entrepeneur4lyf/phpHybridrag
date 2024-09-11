<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

use Psr\SimpleCache\CacheInterface;
use HybridRAG\Exception\HybridRAGException;
use HybridRAG\Logging\Logger;

/**
 * Class KnowledgeGraphBuilder
 *
 * This class is responsible for building and managing the knowledge graph.
 */
class KnowledgeGraphBuilder
{
    /**
     * KnowledgeGraphBuilder constructor.
     *
     * @param GraphDatabaseInterface $dbManager The graph database manager
     * @param CacheInterface $cache The cache interface
     * @param Logger $logger The logger
     */
    public function __construct(
        private GraphDatabaseInterface $dbManager,
        private CacheInterface $cache,
        private Logger $logger
    ) {}

    /**
     * Add an entity to the knowledge graph.
     *
     * @param Entity $entity The entity to add
     * @return string The ID of the added entity
     * @throws HybridRAGException If adding the entity fails
     */
    public function addEntity(Entity $entity): string
    {
        try {
            $this->logger->info("Adding entity to knowledge graph", ['entity' => $entity->getId()]);
            $id = $this->dbManager->addNode($entity->getCollection(), $entity->getProperties());
            $this->logger->info("Entity added successfully to knowledge graph", ['entity' => $id]);
            return $id;
        } catch (\Exception $e) {
            $this->logger->error("Failed to add entity to knowledge graph", ['entity' => $entity->getId(), 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to add entity to knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Add a relationship to the knowledge graph.
     *
     * @param Relationship $relationship The relationship to add
     * @return string The ID of the added relationship
     * @throws HybridRAGException If adding the relationship fails
     */
    public function addRelationship(Relationship $relationship): string
    {
        try {
            $this->logger->info("Adding relationship to knowledge graph", ['from' => $relationship->getFromId(), 'to' => $relationship->getToId()]);
            $id = $this->dbManager->addEdge(
                $relationship->getCollection(),
                $relationship->getFromId(),
                $relationship->getToId(),
                $relationship->getAttributes()
            );
            $this->logger->info("Relationship added successfully to knowledge graph", ['relationship' => $id]);
            return $id;
        } catch (\Exception $e) {
            $this->logger->error("Failed to add relationship to knowledge graph", ['from' => $relationship->getFromId(), 'to' => $relationship->getToId(), 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to add relationship to knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get an entity from the knowledge graph.
     *
     * @param string $id The ID of the entity to get
     * @return Entity|null The entity, or null if not found
     * @throws HybridRAGException If retrieving the entity fails
     */
    public function getEntity(string $id): ?Entity
    {
        try {
            $this->logger->info("Retrieving entity from knowledge graph", ['id' => $id]);
            $cacheKey = "entity_{$id}";
            if ($this->cache->has($cacheKey)) {
                $this->logger->info("Entity retrieved from cache", ['id' => $id]);
                return $this->cache->get($cacheKey);
            }

            $nodeData = $this->dbManager->getNode($id);
            if ($nodeData === null) {
                $this->logger->info("Entity not found in knowledge graph", ['id' => $id]);
                return null;
            }

            $entity = new Entity($nodeData['_collection'], $nodeData);
            $this->cache->set($cacheKey, $entity, 3600); // Cache for 1 hour
            $this->logger->info("Entity retrieved successfully from knowledge graph", ['id' => $id]);
            return $entity;
        } catch (\Exception $e) {
            $this->logger->error("Failed to retrieve entity from knowledge graph", ['id' => $id, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to retrieve entity from knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get a relationship from the knowledge graph.
     *
     * @param string $id The ID of the relationship to get
     * @return Relationship|null The relationship, or null if not found
     * @throws HybridRAGException If retrieving the relationship fails
     */
    public function getRelationship(string $id): ?Relationship
    {
        try {
            $this->logger->info("Retrieving relationship from knowledge graph", ['id' => $id]);
            $cacheKey = "relationship_{$id}";
            if ($this->cache->has($cacheKey)) {
                $this->logger->info("Relationship retrieved from cache", ['id' => $id]);
                return $this->cache->get($cacheKey);
            }

            $edgeData = $this->dbManager->getEdge($id);
            if ($edgeData === null) {
                $this->logger->info("Relationship not found in knowledge graph", ['id' => $id]);
                return null;
            }

            $from = $this->getEntity($edgeData['_from']);
            $to = $this->getEntity($edgeData['_to']);
            $relationship = new Relationship($edgeData['_collection'], $from, $to, $edgeData);
            $this->cache->set($cacheKey, $relationship, 3600); // Cache for 1 hour
            $this->logger->info("Relationship retrieved successfully from knowledge graph", ['id' => $id]);
            return $relationship;
        } catch (\Exception $e) {
            $this->logger->error("Failed to retrieve relationship from knowledge graph", ['id' => $id, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to retrieve relationship from knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update an entity in the knowledge graph.
     *
     * @param Entity $entity The entity to update
     * @throws HybridRAGException If updating the entity fails
     */
    public function updateEntity(Entity $entity): void
    {
        try {
            $this->logger->info("Updating entity in knowledge graph", ['entity' => $entity->getId()]);
            $this->dbManager->updateNode($entity->getId(), $entity->getProperties());
            $this->logger->info("Entity updated successfully in knowledge graph", ['entity' => $entity->getId()]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to update entity in knowledge graph", ['entity' => $entity->getId(), 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to update entity in knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update a relationship in the knowledge graph.
     *
     * @param Relationship $relationship The relationship to update
     * @throws HybridRAGException If updating the relationship fails
     */
    public function updateRelationship(Relationship $relationship): void
    {
        try {
            $this->logger->info("Updating relationship in knowledge graph", ['relationship' => $relationship->getId()]);
            $this->dbManager->updateEdge($relationship->getId(), $relationship->getAttributes());
            $this->logger->info("Relationship updated successfully in knowledge graph", ['relationship' => $relationship->getId()]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to update relationship in knowledge graph", ['relationship' => $relationship->getId(), 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to update relationship in knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute a query on the knowledge graph.
     *
     * @param string $aql The AQL query to execute
     * @param array $bindVars The bind variables for the query
     * @return array The query results
     * @throws HybridRAGException If executing the query fails
     */
    public function query(string $aql, array $bindVars = []): array
    {
        try {
            $this->logger->info("Executing query on knowledge graph", ['query' => $aql]);
            $result = $this->dbManager->query($aql, $bindVars);
            $this->logger->info("Query executed successfully on knowledge graph", ['resultCount' => count($result)]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to execute query on knowledge graph", ['query' => $aql, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to execute query on knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Perform a depth-first search on the knowledge graph.
     *
     * @param string $startId The ID of the starting node
     * @param int $maxDepth The maximum depth to search
     * @return array The search results
     * @throws HybridRAGException If performing the search fails
     */
    public function depthFirstSearch(string $startId, int $maxDepth = 3): array
    {
        try {
            $this->logger->info("Performing depth-first search on knowledge graph", ['startId' => $startId, 'maxDepth' => $maxDepth]);
            $aql = "
                FOR v, e, p IN 1..@maxDepth
                OUTBOUND @startVertex GRAPH @graphName
                OPTIONS {bfs: false, uniqueVertices: 'global'}
                RETURN {vertex: v, edge: e, path: p}
            ";
            $bindVars = [
                'startVertex' => $startId,
                'maxDepth' => $maxDepth,
                'graphName' => $this->dbManager->getGraphName(),
            ];
            $result = $this->query($aql, $bindVars);
            $this->logger->info("Depth-first search executed successfully on knowledge graph", ['resultCount' => count($result)]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to perform depth-first search on knowledge graph", ['startId' => $startId, 'maxDepth' => $maxDepth, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to perform depth-first search on knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Perform a breadth-first search on the knowledge graph.
     *
     * @param string $startId The ID of the starting node
     * @param int $maxDepth The maximum depth to search
     * @return array The search results
     * @throws HybridRAGException If performing the search fails
     */
    public function breadthFirstSearch(string $startId, int $maxDepth = 3): array
    {
        try {
            $this->logger->info("Performing breadth-first search on knowledge graph", ['startId' => $startId, 'maxDepth' => $maxDepth]);
            $aql = "
                FOR v, e, p IN 1..@maxDepth
                OUTBOUND @startVertex GRAPH @graphName
                OPTIONS {bfs: true, uniqueVertices: 'global'}
                RETURN {vertex: v, edge: e, path: p}
            ";
            $bindVars = [
                'startVertex' => $startId,
                'maxDepth' => $maxDepth,
                'graphName' => $this->dbManager->getGraphName(),
            ];
            $result = $this->query($aql, $bindVars);
            $this->logger->info("Breadth-first search executed successfully on knowledge graph", ['resultCount' => count($result)]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to perform breadth-first search on knowledge graph", ['startId' => $startId, 'maxDepth' => $maxDepth, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to perform breadth-first search on knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Find the shortest path between two nodes in the knowledge graph.
     *
     * @param string $startId The ID of the starting node
     * @param string $endId The ID of the ending node
     * @return array The shortest path
     * @throws HybridRAGException If finding the shortest path fails
     */
    public function shortestPath(string $startId, string $endId): array
    {
        try {
            $this->logger->info("Finding shortest path on knowledge graph", ['startId' => $startId, 'endId' => $endId]);
            $aql = "
                FOR v, e IN OUTBOUND
                SHORTEST_PATH @startVertex TO @endVertex
                GRAPH @graphName
                RETURN {vertex: v, edge: e}
            ";
            $bindVars = [
                'startVertex' => $startId,
                'endVertex' => $endId,
                'graphName' => $this->dbManager->getGraphName(),
            ];
            $result = $this->query($aql, $bindVars);
            $this->logger->info("Shortest path found successfully on knowledge graph", ['resultCount' => count($result)]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to find shortest path on knowledge graph", ['startId' => $startId, 'endId' => $endId, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to find shortest path on knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Calculate centrality for nodes in the knowledge graph.
     *
     * @param string $centralityType The type of centrality to calculate
     * @return array The centrality results
     * @throws HybridRAGException If calculating centrality fails
     */
    public function calculateCentrality(string $centralityType = 'betweenness'): array
    {
        try {
            $this->logger->info("Calculating centrality on knowledge graph", ['centralityType' => $centralityType]);
            $aql = "
                RETURN CENTRALITY(
                    @graphName,
                    @centralityType
                )
            ";
            $bindVars = [
                'graphName' => $this->dbManager->getGraphName(),
                'centralityType' => $centralityType,
            ];
            $result = $this->query($aql, $bindVars);
            $this->logger->info("Centrality calculated successfully on knowledge graph", ['resultCount' => count($result)]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Failed to calculate centrality on knowledge graph", ['centralityType' => $centralityType, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to calculate centrality on knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Batch import entities into the knowledge graph.
     *
     * @param array $entities The entities to import
     * @throws HybridRAGException If batch importing entities fails
     */
    public function batchImportEntities(array $entities): void
    {
        try {
            $this->logger->info("Batch importing entities to knowledge graph", ['entityCount' => count($entities)]);
            foreach ($entities as $entity) {
                $this->addEntity($entity);
            }
            $this->logger->info("Entities batch imported successfully to knowledge graph");
        } catch (\Exception $e) {
            $this->logger->error("Failed to batch import entities to knowledge graph", ['error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to batch import entities to knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Batch import relationships into the knowledge graph.
     *
     * @param array $relationships The relationships to import
     * @throws HybridRAGException If batch importing relationships fails
     */
    public function batchImportRelationships(array $relationships): void
    {
        try {
            $this->logger->info("Batch importing relationships to knowledge graph", ['relationshipCount' => count($relationships)]);
            foreach ($relationships as $relationship) {
                $this->addRelationship($relationship);
            }
            $this->logger->info("Relationships batch imported successfully to knowledge graph");
        } catch (\Exception $e) {
            $this->logger->error("Failed to batch import relationships to knowledge graph", ['error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to batch import relationships to knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create indexes on the knowledge graph.
     *
     * @throws HybridRAGException If creating indexes fails
     */
    public function createIndexes(): void
    {
        try {
            $this->logger->info("Creating indexes on knowledge graph");
            $this->dbManager->createIndex('entities', ['name'], 'hash', false);
            $this->dbManager->createIndex('entities', ['type'], 'hash', false);
            $this->dbManager->createIndex('relationships', ['type'], 'hash', false);
            $this->logger->info("Indexes created successfully on knowledge graph");
        } catch (\Exception $e) {
            $this->logger->error("Failed to create indexes on knowledge graph", ['error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to create indexes on knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Optimize the knowledge graph.
     *
     * @throws HybridRAGException If optimizing the graph fails
     */
    public function optimizeGraph(): void
    {
        try {
            $this->logger->info("Optimizing knowledge graph");
            $this->dbManager->optimize();
            $this->logger->info("Knowledge graph optimized successfully");
        } catch (\Exception $e) {
            $this->logger->error("Failed to optimize knowledge graph", ['error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to optimize knowledge graph: " . $e->getMessage(), 0, $e);
        }
    }
}
