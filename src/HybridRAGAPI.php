<?php

declare(strict_types=1);

namespace HybridRAG;

use HybridRAG\HybridRAG\HybridRAGInterface;
use HybridRAG\Config\Configuration;

class HybridRAGAPI
{
    private HybridRAGInterface $hybridRAG;
    private Configuration $config;

    public function __construct(HybridRAGInterface $hybridRAG, Configuration $config)
    {
        $this->hybridRAG = $hybridRAG;
        $this->config = $config;
    }

    public function addDocument(string $id, string $content, array $metadata = []): self
    {
        $this->hybridRAG->addDocument($id, $content, $metadata);
        return $this;
    }

    public function query(string $query): string
    {
        $context = $this->hybridRAG->retrieveContext($query);
        return $this->hybridRAG->generateAnswer($query, $context);
    }

    public function improveModel(array $unlabeledSamples, int $numSamples): array
    {
        return $this->hybridRAG->improveModel($unlabeledSamples, $numSamples);
    }

    public function evaluate(string $query, string $answer, array $context, array $relevantContext): array
    {
        return $this->hybridRAG->evaluatePerformance($query, $answer, $context, $relevantContext);
    }

    public function configure(): Configuration
    {
        return $this->config;
    }
}