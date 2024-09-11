<?php

declare(strict_types=1);

namespace HybridRAG\GraphRAG;

use HybridRAG\KnowledgeGraph\KnowledgeGraphBuilder;
use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\LanguageModel\LanguageModelInterface;
use HybridRAG\Logging\Logger;
use HybridRAG\Exception\HybridRAGException;

/**
 * Class GraphRAG
 *
 * Implements the GraphRAGInterface for Graph-based Retrieval-Augmented Generation.
 */
class GraphRAG implements GraphRAGInterface
{
    /**
     * GraphRAG constructor.
     *
     * @param KnowledgeGraphBuilder $kg The knowledge graph builder
     * @param EmbeddingInterface $embedding The embedding interface
     * @param LanguageModelInterface $languageModel The language model interface
     * @param Logger $logger The logger instance
     * @param int $maxDepth The maximum depth for graph traversal
     * @param float $entitySimilarityThreshold The threshold for entity similarity
     */
    public function __construct(
        private KnowledgeGraphBuilder $kg,
        private EmbeddingInterface $embedding,
        private LanguageModelInterface $languageModel,
        private Logger $logger,
        private int $maxDepth = 2,
        private float $entitySimilarityThreshold = 0.7
    ) {}

    /**
     * Add an entity to the knowledge graph.
     *
     * @param string $id The unique identifier for the entity
     * @param string $content The content of the entity
     * @param array $metadata Additional metadata associated with the entity
     * @return string The ID of the added entity
     * @throws HybridRAGException If adding the entity fails
     */
    public function addEntity(string $id, string $content, array $metadata = []): string
    {
        try {
            $this->logger->info("Adding entity to GraphRAG", ['id' => $id]);
            $entity = new \HybridRAG\KnowledgeGraph\Entity('entities', array_merge(
                ['id' => $id, 'content' => $content],
                $metadata,
                ['embedding' => $this->embedding->embed($content)]
            ));
            $entityId = $this->kg->addEntity($entity);
            $this->logger->info("Entity added successfully to GraphRAG", ['id' => $id, 'entityId' => $entityId]);
            return $entityId;
        } catch (\Exception $e) {
            $this->logger->error("Failed to add entity to GraphRAG", ['id' => $id, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to add entity to GraphRAG: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Add a relationship between two entities in the knowledge graph.
     *
     * @param string $fromId The ID of the source entity
     * @param string $toId The ID of the target entity
     * @param string $type The type of the relationship
     * @param array $attributes Additional attributes for the relationship
     * @return string The ID of the added relationship
     * @throws HybridRAGException If adding the relationship fails
     */
    public function addRelationship(string $fromId, string $toId, string $type, array $attributes = []): string
    {
        try {
            $this->logger->info("Adding relationship to GraphRAG", ['fromId' => $fromId, 'toId' => $toId, 'type' => $type]);
            $from = $this->kg->getEntity($fromId);
            $to = $this->kg->getEntity($toId);
            if (!$from || !$to) {
                $this->logger->error("One or both entities do not exist", ['fromId' => $fromId, 'toId' => $toId]);
                throw new \InvalidArgumentException("One or both entities do not exist.");
            }
            $relationship = new \HybridRAG\KnowledgeGraph\Relationship('relationships', $from, $to, array_merge(['type' => $type], $attributes));
            $relationshipId = $this->kg->addRelationship($relationship);
            $this->logger->info("Relationship added successfully to GraphRAG", ['fromId' => $fromId, 'toId' => $toId, 'type' => $type, 'relationshipId' => $relationshipId]);
            return $relationshipId;
        } catch (\Exception $e) {
            $this->logger->error("Failed to add relationship to GraphRAG", ['fromId' => $fromId, 'toId' => $toId, 'type' => $type, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to add relationship to GraphRAG: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Retrieve context for a given query from the knowledge graph.
     *
     * @param string $query The query string
     * @param int|null $maxDepth The maximum depth to traverse in the graph (optional)
     * @return array An array of relevant context from the graph
     * @throws HybridRAGException If retrieving context fails
     */
    public function retrieveContext(string $query, int $maxDepth = null): array
    {
        try {
            $this->logger->info("Retrieving context from GraphRAG", ['query' => $query, 'maxDepth' => $maxDepth ?? $this->maxDepth]);
            $maxDepth = $maxDepth ?? $this->maxDepth;
            $entities = $this->disambiguateEntities($query);
            $context = [];

            foreach ($entities as $entity) {
                $subgraph = $this->kg->depthFirstSearch($entity['id'], $maxDepth);
                $context = array_merge($context, $this->formatSubgraph($subgraph));
            }

            $this->logger->info("Context retrieved successfully from GraphRAG", ['query' => $query, 'contextSize' => count($context)]);
            return $context;
        } catch (\Exception $e) {
            $this->logger->error("Failed to retrieve context from GraphRAG", ['query' => $query, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to retrieve context from GraphRAG: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Generate an answer based on the query and provided context from the graph.
     *
     * @param string $query The query string
     * @param array $context The context retrieved from the graph
     * @return string The generated answer
     * @throws HybridRAGException If generating the answer fails
     */
    public function generateAnswer(string $query, array $context): string
    {
        try {
            $this->logger->info("Generating answer in GraphRAG", ['query' => $query]);
            $formattedContext = $this->formatContextForLLM($context);
            $prompt = $this->constructPrompt($query, $formattedContext);
            $answer = $this->languageModel->generateResponse($prompt, $context);
            $this->logger->info("Answer generated successfully in GraphRAG", ['query' => $query]);
            return $answer;
        } catch (\Exception $e) {
            $this->logger->error("Failed to generate answer in GraphRAG", ['query' => $query, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to generate answer in GraphRAG: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Disambiguate entities from the query.
     *
     * @param string $query The query string
     * @return array An array of disambiguated entities
     */
    private function disambiguateEntities(string $query): array
    {
        $queryEmbedding = $this->embedding->embed($query);
        
        $aql = "
            FOR entity IN entities
            LET similarity = COSINE_SIMILARITY(@queryEmbedding, entity.embedding)
            FILTER similarity >= @threshold
            SORT similarity DESC
            LIMIT 5
            RETURN {id: entity._id, name: entity.content, similarity: similarity}
        ";
        
        $bindVars = [
            'queryEmbedding' => $queryEmbedding,
            'threshold' => $this->entitySimilarityThreshold
        ];
        return $this->kg->query($aql, $bindVars);
    }

    /**
     * Format the subgraph into a standardized structure.
     *
     * @param array $subgraph The subgraph to format
     * @return array The formatted subgraph
     */
    private function formatSubgraph(array $subgraph): array
    {
        $formattedContext = [];
        foreach ($subgraph as $item) {
            if (isset($item['vertex'])) {
                $formattedContext[] = [
                    'type' => 'entity',
                    'id' => $item['vertex']['_id'],
                    'content' => $item['vertex']['content'],
                    'metadata' => array_diff_key($item['vertex'], ['_id' => 1, '_key' => 1, '_rev' => 1, 'content' => 1, 'embedding' => 1])
                ];
            } elseif (isset($item['edge'])) {
                $formattedContext[] = [
                    'type' => 'relationship',
                    'id' => $item['edge']['_id'],
                    'from' => $item['edge']['_from'],
                    'to' => $item['edge']['_to'],
                    'relationshipType' => $item['edge']['type'],
                    'metadata' => array_diff_key($item['edge'], ['_id' => 1, '_key' => 1, '_rev' => 1, '_from' => 1, '_to' => 1, 'type' => 1])
                ];
            }
        }
        return $formattedContext;
    }

    /**
     * Format the context for the language model.
     *
     * @param array $context The context to format
     * @return string The formatted context as a string
     */
    private function formatContextForLLM(array $context): string
    {
        $formattedContext = "";
        foreach ($context as $item) {
            if ($item['type'] === 'entity') {
                $formattedContext .= "Entity: {$item['content']}\n";
                $formattedContext .= "Metadata: " . json_encode($item['metadata']) . "\n\n";
            } elseif ($item['type'] === 'relationship') {
                $formattedContext .= "Relationship: {$item['relationshipType']} (from {$item['from']} to {$item['to']})\n";
                $formattedContext .= "Metadata: " . json_encode($item['metadata']) . "\n\n";
            }
        }
        return $formattedContext;
    }

    /**
     * Construct a prompt for the language model.
     *
     * @param string $query The query string
     * @param string $context The formatted context
     * @return string The constructed prompt
     */
    private function constructPrompt(string $query, string $context): string
    {
        return <<<EOT
        Given the following knowledge graph context, please answer the question.

        Context:
        $context

        Question: $query

        Answer:
        EOT;
    }

    /**
     * Set the maximum depth for graph traversal.
     *
     * @param int $maxDepth The maximum depth to set
     * @return self
     */
    public function setMaxDepth(int $maxDepth): self
    {
        $this->maxDepth = $maxDepth;
        return $this;
    }

    /**
     * Set the entity similarity threshold.
     *
     * @param float $threshold The threshold to set
     * @return self
     */
    public function setEntitySimilarityThreshold(float $threshold): self
    {
        $this->entitySimilarityThreshold = $threshold;
        return $this;
    }
}
