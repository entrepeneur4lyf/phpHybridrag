<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

use ArangoDBClient\Document;

class Entity
{
    private Document $document;

    public function __construct(string $collection, array $properties)
    {
        $this->document = new Document();
        $this->document->setCollection($collection);
        foreach ($properties as $key => $value) {
            $this->document->set($key, $value);
        }
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function getId(): string
    {
        return $this->document->getId();
    }

    public function getProperties(): array
    {
        return $this->document->getAll();
    }
}