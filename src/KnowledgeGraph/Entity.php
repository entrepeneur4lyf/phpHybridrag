<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

use ArangoDBClient\Document;

/**
 * Class Entity
 *
 * Represents an entity in the knowledge graph.
 */
class Entity
{
    private string $id; // Ensure this is a string type
    private array $properties;

    /**
     * Entity constructor.
     *
     * @param string $id The ID of the entity
     * @param array $properties The properties of the entity
     */
    public function __construct(string $id, array $properties = [])
    {
        $this->id = $id; // Initialize the ID
        $this->properties = $properties;
    }

    /**
     * Get the underlying ArangoDB document.
     *
     * @return Document The ArangoDB document
     */
    public function getDocument(): Document
    {
        return $this->document;
    }

    /**
     * Get the ID of the entity.
     *
     * @return string The entity ID
     */
    public function getId(): string
    {
        if (!$this->id) {
            throw new \RuntimeException("Entity ID is not set.");
        }
        return $this->id;
    }

    /**
     * Get all properties of the entity.
     *
     * @return array The entity properties
     */
    public function getProperties(): array
    {
        return $this->document->getAll();
    }
}
