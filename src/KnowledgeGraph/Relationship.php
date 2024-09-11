<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

use ArangoDBClient\Edge;

/**
 * Class Relationship
 *
 * Represents a relationship (edge) in the knowledge graph.
 */
class Relationship
{
    private Edge $edge;

    /**
     * Relationship constructor.
     *
     * @param string $collection The collection to which this relationship belongs
     * @param Entity $from The source entity of the relationship
     * @param Entity $to The target entity of the relationship
     * @param array $attributes The attributes of the relationship
     */
    public function __construct(string $collection, Entity $from, Entity $to, array $attributes = [])
    {
        $this->edge = new Edge();
        $this->edge->setCollection($collection);
        $this->edge->setFrom($from->getId());
        $this->edge->setTo($to->getId());
        foreach ($attributes as $key => $value) {
            $this->edge->set($key, $value);
        }
    }

    /**
     * Get the underlying ArangoDB edge.
     *
     * @return Edge The ArangoDB edge
     */
    public function getEdge(): Edge
    {
        return $this->edge;
    }

    /**
     * Get the ID of the relationship.
     *
     * @return string The relationship ID
     */
    public function getId(): string
    {
        return $this->edge->getId();
    }

    /**
     * Get all attributes of the relationship.
     *
     * @return array The relationship attributes
     */
    public function getAttributes(): array
    {
        return $this->edge->getAll();
    }

    /**
     * Get the ID of the source entity.
     *
     * @return string The source entity ID
     */
    public function getFromId(): string
    {
        return $this->edge->getFrom();
    }

    /**
     * Get the ID of the target entity.
     *
     * @return string The target entity ID
     */
    public function getToId(): string
    {
        return $this->edge->getTo();
    }

    /**
     * Get the collection of the relationship.
     *
     * @return string The collection name
     */
    public function getCollection(): string
    {
        return $this->edge->getCollection();
    }
}
