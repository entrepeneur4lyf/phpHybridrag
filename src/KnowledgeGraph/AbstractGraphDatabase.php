<?php

declare(strict_types=1);

namespace HybridRAG\KnowledgeGraph;

abstract class AbstractGraphDatabase implements GraphDatabaseInterface
{
    protected array $config;

    public function connect(array $config): void
    {
        $this->config = $config;
        $this->initializeConnection();
    }

    abstract protected function initializeConnection(): void;
}