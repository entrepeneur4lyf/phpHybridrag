<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

use ArangoDBClient\Edge;

class Relationship
{
    private Edge $edge;

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

    public function getEdge(): Edge
    {
        return $this->edge;
    }

    public function getId(): string
    {
        return $this->edge->getId();
    }

    public function getAttributes(): array
    {
        return $this->edge->getAll();
    }
}