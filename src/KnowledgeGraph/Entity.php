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
    private Document $document;

    /**
     * Entity constructor.
     *
     * @param string $collection The collection to which this entity belongs
     * @param array $properties The properties of the entity
     */
    public function __construct(string $collection, array $properties)
    {
        $this->document = new Document();
        $this->document->setCollection($collection);
        foreach ($properties as $key => $value) {
            $this->document->set($key, $value);
        }
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
        return $this->document->getId();
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
