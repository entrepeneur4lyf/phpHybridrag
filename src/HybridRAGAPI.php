<?php

declare(strict_types=1);

namespace HybridRAG;

use HybridRAG\HybridRAG\HybridRAGInterface;
use HybridRAG\Config\Configuration;

/**
 * Class HybridRAGAPI
 *
 * This class provides a high-level API for interacting with the HybridRAG system.
 */
class HybridRAGAPI
{
    private HybridRAGInterface $hybridRAG;
    private Configuration $config;

    /**
     * HybridRAGAPI constructor.
     *
     * @param HybridRAGInterface $hybridRAG The HybridRAG implementation
     * @param Configuration $config The configuration object
     */
    public function __construct(HybridRAGInterface $hybridRAG, Configuration $config)
    {
        $this->hybridRAG = $hybridRAG;
        $this->config = $config;
    }

    /**
     * Add a document to the HybridRAG system.
     *
     * @param string $id The document ID
     * @param string $content The document content
     * @param array $metadata Additional metadata
     * @return self
     */
    public function addDocument(string $id, string $content, array $metadata = []): self
    {
        $this->hybridRAG->addDocument($id, $content, $metadata);
        return $this;
    }

    /**
     * Query the HybridRAG system and get an answer.
     *
     * @param string $query The query string
     * @return string The generated answer
     */
    public function query(string $query): string
    {
        $context = $this->hybridRAG->retrieveContext($query);
        return $this->hybridRAG->generateAnswer($query, $context);
    }

    /**
     * Improve the model using active learning.
     *
     * @param array $unlabeledSamples The unlabeled samples
     * @param int $numSamples The number of samples to select
     * @return array The selected samples
     */
    public function improveModel(array $unlabeledSamples, int $numSamples): array
    {
        return $this->hybridRAG->improveModel($unlabeledSamples, $numSamples);
    }

    /**
     * Evaluate the performance of the system.
     *
     * @param string $query The query string
     * @param string $answer The generated answer
     * @param array $context The context used
     * @param array $relevantContext The relevant context
     * @return array The evaluation report
     */
    public function evaluate(string $query, string $answer, array $context, array $relevantContext): array
    {
        return $this->hybridRAG->evaluatePerformance($query, $answer, $context, $relevantContext);
    }

    /**
     * Get the configuration object.
     *
     * @return Configuration The configuration object
     */
    public function configure(): Configuration
    {
        return $this->config;
    }
}
