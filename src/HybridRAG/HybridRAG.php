<?php

declare(strict_types=1);

namespace HybridRAG\HybridRAG;

use HybridRAG\VectorRAG\VectorRAGInterface;
use HybridRAG\GraphRAG\GraphRAGInterface;
use HybridRAG\Reranker\RerankerInterface;
use HybridRAG\LanguageModel\LanguageModelInterface;
use HybridRAG\TopicModeling\LDATopicModeler;
use HybridRAG\SentimentAnalysis\LexiconSentimentAnalyzer;
use HybridRAG\ActiveLearning\UncertaintySampler;
use HybridRAG\Evaluation\EvaluationMetrics;
use HybridRAG\Config\Configuration;
use HybridRAG\Logging\Logger;
use HybridRAG\Exception\HybridRAGException;

class HybridRAG implements HybridRAGInterface
{
    private NERClassifier $nerClassifier;
    private LDATopicModeler $topicModeler;
    private LexiconSentimentAnalyzer $sentimentAnalyzer;
    private UncertaintySampler $activeLearner;
    private EvaluationMetrics $evaluationMetrics;
    private Configuration $config;
    private Logger $logger;

    public function __construct(
        private VectorRAGInterface $vectorRAG,
        private GraphRAGInterface $graphRAG,
        private RerankerInterface $reranker,
        private LanguageModelInterface $languageModel,
        Configuration $config
    ) {
        $this->config = $config;
        $this->initializeLogger();
        $this->initializeComponents();
    }

    private function initializeLogger(): void
    {
        $logConfig = $this->config->get('logging');
        $this->logger = new Logger(
            $logConfig['name'],
            $logConfig['path'],
            $logConfig['level'],
            $logConfig['debug_mode']
        );
    }

    private function initializeComponents(): void
    {
        try {
            $this->nerClassifier = new NERClassifier();
            $this->topicModeler = new LDATopicModeler();
            $this->sentimentAnalyzer = new LexiconSentimentAnalyzer($this->config->get('sentiment_analysis.lexicon_path'));
            $this->activeLearner = new UncertaintySampler($this->nerClassifier);
            $this->evaluationMetrics = new EvaluationMetrics($this->languageModel);
            $this->trainNERClassifier();
        } catch (\Exception $e) {
            $this->logger->critical('Failed to initialize components: ' . $e->getMessage());
            throw new HybridRAGException('Failed to initialize HybridRAG components', 0, $e);
        }
    }

    public function addDocument(string $id, string $content, array $metadata = []): void
    {
        try {
            $this->logger->info("Adding document", ['id' => $id]);
            
            $this->vectorRAG->addDocument($id, $content, $metadata);
            $this->graphRAG->addEntity($id, $content, $metadata);
            
            if (isset($metadata['related_to'])) {
                foreach ($metadata['related_to'] as $relatedId) {
                    $this->graphRAG->addRelationship($id, $relatedId, 'RELATED_TO');
                }
            }

            $entities = $this->extractEntities($content);
            foreach ($entities as $entity) {
                $entityId = $this->graphRAG->addEntity($entity, $entity, ['type' => 'extracted']);
                $this->graphRAG->addRelationship($id, $entityId, 'CONTAINS');
            }

            $topics = $this->topicModeler->fit([$content]);
            $metadata['topics'] = $topics['doc_topic_dist'][0];

            $sentiment = $this->sentimentAnalyzer->analyzeSentiment($content);
            $metadata['sentiment'] = $sentiment;

            $this->logger->info("Document added successfully", ['id' => $id]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to add document", ['id' => $id, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to add document: {$e->getMessage()}", 0, $e);
        }
    }

    public function retrieveContext(string $query): array
    {
        try {
            $this->logger->info("Retrieving context", ['query' => $query]);
            
            $vectorContext = $this->vectorRAG->retrieveContext($query, $this->config->get('hybridrag.top_k'));
            $graphContext = $this->graphRAG->retrieveContext($query, $this->config->get('hybridrag.max_depth'));

            $mergedContext = $this->mergeContext($vectorContext, $graphContext);
            
            $queryTopics = $this->topicModeler->fit([$query])['doc_topic_dist'][0];
            $mergedContext = $this->incorporateTopics($mergedContext, $queryTopics);

            $querySentiment = $this->sentimentAnalyzer->analyzeSentiment($query);
            $mergedContext = $this->incorporateSentiment($mergedContext, $querySentiment);

            $rerankedContext = $this->reranker->rerank($query, $mergedContext, $this->config->get('reranker.top_k'));

            $this->logger->info("Context retrieved successfully", ['query' => $query, 'context_size' => count($rerankedContext)]);
            
            return $rerankedContext;
        } catch (\Exception $e) {
            $this->logger->error("Failed to retrieve context", ['query' => $query, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to retrieve context: {$e->getMessage()}", 0, $e);
        }
    }

    public function generateAnswer(string $query, array $context): string
    {
        try {
            $this->logger->info("Generating answer", ['query' => $query]);
            
            $formattedContext = $this->formatContextForLLM($context);
            $prompt = $this->constructPrompt($query, $formattedContext);
            $answer = $this->languageModel->generateResponse($prompt, $context);

            $this->logger->info("Answer generated successfully", ['query' => $query]);
            
            return $answer;
        } catch (\Exception $e) {
            $this->logger->error("Failed to generate answer", ['query' => $query, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to generate answer: {$e->getMessage()}", 0, $e);
        }
    }

    private function mergeContext(array $vectorContext, array $graphContext): array
    {
        $mergedContext = [];
        $vectorScore = $this->config->get('hybridrag.vector_weight');
        $graphScore = 1 - $vectorScore;

        foreach ($vectorContext as $item) {
            $mergedContext[] = [
                'content' => $item['content'],
                'metadata' => $item['metadata'],
                'score' => $item['score'] * $vectorScore,
                'source' => 'vector'
            ];
        }

        foreach ($graphContext as $item) {
            $existingIndex = $this->findExistingContext($mergedContext, $item['id']);
            if ($existingIndex !== false) {
                $mergedContext[$existingIndex]['score'] += $graphScore;
                $mergedContext[$existingIndex]['source'] = 'both';
            } else {
                $mergedContext[] = [
                    'content' => $item['content'],
                    'metadata' => $item['metadata'],
                    'score' => $graphScore,
                    'source' => 'graph'
                ];
            }
        }

        usort($mergedContext, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $mergedContext;
    }

    private function findExistingContext(array $mergedContext, string $id): int|false
    {
        foreach ($mergedContext as $index => $item) {
            if (isset($item['metadata']['id']) && $item['metadata']['id'] === $id) {
                return $index;
            }
        }
        return false;
    }

    private function formatContextForLLM(array $context): string
    {
        $formattedContext = "";
        foreach ($context as $item) {
            $formattedContext .= "Source: {$item['source']}\n";
            $formattedContext .= "Content: {$item['content']}\n";
            $formattedContext .= "Metadata: " . json_encode($item['metadata']) . "\n\n";
        }
        return $formattedContext;
    }

    private function constructPrompt(string $query, string $context): string
    {
        return <<<EOT
        Given the following context, please answer the question.

        Context:
        $context

        Question: $query

        Answer:
        EOT;
    }

    private function extractEntities(string $content): array
    {
        $tokens = explode(' ', $content); // Simple tokenization
        $predictions = $this->nerClassifier->predict($tokens);
        
        $entities = [];
        for ($i = 0; $i < count($tokens); $i++) {
            if ($predictions[$i] === 'ENTITY') {
                $entities[] = $tokens[$i];
            }
        }
        
        return $entities;
    }

    private function trainNERClassifier(): void
    {
        // This is a placeholder. In a real implementation, you would load a pre-labeled dataset
        $samples = [
            'John is from New York',
            'Apple Inc. was founded by Steve Jobs',
            'The Eiffel Tower is in Paris'
        ];
        $labels = [
            ['ENTITY', 'O', 'O', 'ENTITY', 'ENTITY'],
            ['ENTITY', 'ENTITY', 'O', 'O', 'O', 'ENTITY', 'ENTITY'],
            ['O', 'ENTITY', 'ENTITY', 'O', 'O', 'ENTITY']
        ];
        $this->nerClassifier->train($samples, $labels);
    }

    private function convertPredictionsToEntities(array $tokens, array $predictions): array
    {
        // This is a placeholder for converting predictions to entities
        // In a production environment, you would use a more sophisticated NER system
        return [];
    }

    private function extractFeatures(array $tokens): array
    {
        // This is a placeholder for extracting features
        // In a production environment, you would use a more sophisticated feature extraction system
        return [];
    }

    public function setVectorWeight(float $weight): self
    {
        $this->config->set('hybridrag.vector_weight', $weight);
        return $this;
    }

    public function setTopK(int $topK): self
    {
        $this->config->set('hybridrag.top_k', $topK);
        return $this;
    }

    public function setMaxDepth(int $maxDepth): self
    {
        $this->config->set('hybridrag.max_depth', $maxDepth);
        return $this;
    }

    private function incorporateTopics(array $context, array $queryTopics): array
    {
        foreach ($context as &$item) {
            $topicSimilarity = $this->calculateCosineSimilarity($queryTopics, $item['metadata']['topics'] ?? []);
            $item['score'] *= (1 + $topicSimilarity);
        }
        return $context;
    }

    private function incorporateSentiment(array $context, float $querySentiment): array
    {
        foreach ($context as &$item) {
            $sentimentDifference = abs($querySentiment - ($item['metadata']['sentiment'] ?? 0));
            $item['score'] *= (1 - $sentimentDifference);
        }
        return $context;
    }

    private function calculateCosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        foreach ($a as $key => $valueA) {
            $dotProduct += $valueA * ($b[$key] ?? 0);
            $magnitudeA += $valueA * $valueA;
            $magnitudeB += ($b[$key] ?? 0) * ($b[$key] ?? 0);
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }

    public function improveModel(array $unlabeledSamples, int $numSamples): array
    {
        return $this->activeLearner->selectSamples($unlabeledSamples, $numSamples);
    }

    public function evaluatePerformance(string $query, string $answer, array $context, array $relevantContext): array
    {
        return $this->evaluationMetrics->generateEvaluationReport($query, $answer, $context, $relevantContext);
    }

    public function addImage(string $id, string $filePath, array $metadata = []): void
    {
        $this->vectorRAG->addImage($id, $filePath, $metadata);
        $this->graphRAG->addImage($id, $filePath, $metadata);
    }

    public function addAudio(string $id, string $filePath, array $metadata = []): void
    {
        $this->vectorRAG->addAudio($id, $filePath, $metadata);
        $this->graphRAG->addAudio($id, $filePath, $metadata);
    }

    public function addVideo(string $id, string $filePath, array $metadata = []): void
    {
        $this->vectorRAG->addVideo($id, $filePath, $metadata);
        $this->graphRAG->addVideo($id, $filePath, $metadata);
    }
}