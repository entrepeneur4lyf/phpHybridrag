<?php

declare(strict_types=1);

namespace HybridRAG\VectorRAG;

use HybridRAG\VectorDatabase\VectorDatabaseInterface;
use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\LanguageModel\LanguageModelInterface;
use HybridRAG\Logging\Logger;
use HybridRAG\Exception\HybridRAGException;
use Phpml\Math\Distance\Euclidean;
use HybridRAG\DocumentPreprocessing\DocumentPreprocessorFactory;

class VectorRAG implements VectorRAGInterface
{
    private Euclidean $distance;

    public function __construct(
        private VectorDatabaseInterface $vectorDB,
        private EmbeddingInterface $embedding,
        private LanguageModelInterface $languageModel,
        private Logger $logger
    ) {
        $this->distance = new Euclidean();
    }

    public function addDocument(string $id, string $content, array $metadata = []): void
    {
        try {
            $this->logger->info("Adding document to VectorRAG", ['id' => $id]);
            $vector = $this->embedding->embed($content);
            $this->vectorDB->insert($id, $vector, $metadata);
            $this->logger->info("Document added successfully to VectorRAG", ['id' => $id]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to add document to VectorRAG", ['id' => $id, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to add document to VectorRAG: {$e->getMessage()}", 0, $e);
        }
    }

    public function addImage(string $id, string $filePath, array $metadata = []): void
    {
        $preprocessor = DocumentPreprocessorFactory::create('image', $this->logger);
        $content = $preprocessor->parseDocument($filePath);
        $extractedMetadata = $preprocessor->extractMetadata($filePath);
        $this->addDocument($id, $content, array_merge($metadata, $extractedMetadata));
    }

    public function addAudio(string $id, string $filePath, array $metadata = []): void
    {
        $preprocessor = DocumentPreprocessorFactory::create('audio', $this->logger);
        $content = $preprocessor->parseDocument($filePath);
        $extractedMetadata = $preprocessor->extractMetadata($filePath);
        $this->addDocument($id, $content, array_merge($metadata, $extractedMetadata));
    }

    public function addVideo(string $id, string $filePath, array $metadata = []): void
    {
        $preprocessor = DocumentPreprocessorFactory::create('video', $this->logger);
        $content = $preprocessor->parseDocument($filePath);
        $extractedMetadata = $preprocessor->extractMetadata($filePath);
        $this->addDocument($id, $content, array_merge($metadata, $extractedMetadata));
    }

    public function retrieveContext(string $query, int $topK = 5, array $filters = []): array
    {
        try {
            $this->logger->info("Retrieving context from VectorRAG", ['query' => $query, 'topK' => $topK]);
            $queryVector = $this->embedding->embed($query);
            $results = $this->vectorDB->query($queryVector, $topK);

            // Use Euclidean distance for re-ranking
            usort($results, function($a, $b) use ($queryVector) {
                $distanceA = $this->distance->distance($queryVector, $a['vector']);
                $distanceB = $this->distance->distance($queryVector, $b['vector']);
                return $distanceA <=> $distanceB;
            });

            // Apply filters
            if (!empty($filters)) {
                $results = array_filter($results, function ($result) use ($filters) {
                    foreach ($filters as $key => $value) {
                        if (!isset($result['metadata'][$key]) || $result['metadata'][$key] !== $value) {
                            return false;
                        }
                    }
                    return true;
                });
            }

            $this->logger->info("Context retrieved successfully from VectorRAG", ['query' => $query, 'resultsCount' => count($results)]);
            return $results;
        } catch (\Exception $e) {
            $this->logger->error("Failed to retrieve context from VectorRAG", ['query' => $query, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to retrieve context from VectorRAG: {$e->getMessage()}", 0, $e);
        }
    }

    public function generateAnswer(string $query, array $context): string
    {
        try {
            $this->logger->info("Generating answer in VectorRAG", ['query' => $query]);
            $answer = $this->languageModel->generateResponse($query, $context);
            $this->logger->info("Answer generated successfully in VectorRAG", ['query' => $query]);
            return $answer;
        } catch (\Exception $e) {
            $this->logger->error("Failed to generate answer in VectorRAG", ['query' => $query, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to generate answer in VectorRAG: {$e->getMessage()}", 0, $e);
        }
    }
}