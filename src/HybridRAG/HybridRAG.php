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

/**
 * Class HybridRAG
 *
 * This class implements the HybridRAGInterface and provides the main functionality
 * for the Hybrid Retrieval-Augmented Generation system.
 */
class HybridRAG implements HybridRAGInterface
{
    private NERClassifier $nerClassifier;
    private LDATopicModeler $topicModeler;
    private LexiconSentimentAnalyzer $sentimentAnalyzer;
    private UncertaintySampler $activeLearner;
    private EvaluationMetrics $evaluationMetrics;
    private Configuration $config;
    private Logger $logger;

    /**
     * HybridRAG constructor.
     *
     * @param VectorRAGInterface $vectorRAG The VectorRAG component
     * @param GraphRAGInterface $graphRAG The GraphRAG component
     * @param RerankerInterface $reranker The reranker component
     * @param LanguageModelInterface $languageModel The language model component
     * @param Configuration $config The configuration object
     */
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

    /**
     * Initialize the logger.
     */
    private function initializeLogger(): void
    {
        $this->logger = new Logger(
            $this->config->logging['name'] ?? 'default_logger',
            $this->config->logging['path'] ?? '/tmp/default.log',
            $this->config->logging['level'] ?? 'INFO',
            $this->config->logging['debug_mode'] ?? false
        );
    }

    /**
     * Initialize the components.
     *
     * @throws HybridRAGException If initialization fails
     */
    private function initializeComponents(): void
    {
        try {
            $this->nerClassifier = new NERClassifier();
            $this->topicModeler = new LDATopicModeler();
            $lexiconPath = $this->config->sentiment_analysis['lexicon_path'];
            if ($lexiconPath === null) {
                throw new HybridRAGException('Lexicon path for sentiment analysis is not configured.');
            }
            $this->sentimentAnalyzer = new LexiconSentimentAnalyzer($lexiconPath);
            $this->activeLearner = new UncertaintySampler($this->nerClassifier);
            $this->evaluationMetrics = new EvaluationMetrics($this->languageModel);
            $this->trainNERClassifier();
        } catch (\Exception $e) {
            $this->logger->critical('Failed to initialize components: ' . $e->getMessage());
            throw new HybridRAGException('Failed to initialize HybridRAG components', 0, $e);
        }
    }

    /**
     * Add a document to the system.
     *
     * @param string $id The document ID
     * @param string $content The document content
     * @param array $metadata Additional metadata
     * @throws HybridRAGException If adding the document fails
     */
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

            $entities = $this->extractEntities($content) ?? [];
            foreach ($entities as $entity) {
                $entityId = $this->graphRAG->addEntity($entity, $entity, ['type' => 'extracted']);
                $this->graphRAG->addRelationship($id, $entityId, 'CONTAINS');
            }

            $topics = $this->topicModeler->fit([$content]);
            $metadata['topics'] = $topics['doc_topic_dist'][0] ?? [];

            $sentiment = $this->sentimentAnalyzer->analyzeSentiment($content);
            $metadata['sentiment'] = $sentiment ?? 0;

            $this->logger->info("Document added successfully", ['id' => $id]);
        } catch (\Exception $e) {
            $this->logger->error("Failed to add document", ['id' => $id, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to add document: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Retrieve context for a given query.
     *
     * @param string $query The query string
     * @return array The retrieved context
     * @throws HybridRAGException If retrieving context fails
     */
    public function retrieveContext(string $query): array
    {
        try {
            $this->logger->info("Retrieving context", ['query' => $query]);
            
            $topK = $this->config->hybridrag['top_k'] ?? 5; // Default value if not set
            $maxDepth = $this->config->hybridrag['max_depth'] ?? 2; // Default value if not set

            $vectorContext = $this->vectorRAG->retrieveContext($query, $topK);
            $graphContext = $this->graphRAG->retrieveContext($query, $maxDepth);

            $mergedContext = $this->mergeContext($vectorContext, $graphContext);
            
            $queryTopics = $this->topicModeler->fit([$query])['doc_topic_dist'][0];
            $mergedContext = $this->incorporateTopics($mergedContext, $queryTopics);

            $querySentiment = $this->sentimentAnalyzer->analyzeSentiment($query);
            $mergedContext = $this->incorporateSentiment($mergedContext, $querySentiment);

            $rerankedContext = $this->reranker->rerank($query, $mergedContext, $this->config->reranker['top_k']);

            $this->logger->info("Context retrieved successfully", ['query' => $query, 'context_size' => count($rerankedContext)]);
            
            return $rerankedContext;
        } catch (\Exception $e) {
            $this->logger->error("Failed to retrieve context", ['query' => $query, 'error' => $e->getMessage()]);
            throw new HybridRAGException("Failed to retrieve context: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Generate an answer for a given query and context.
     *
     * @param string $query The query string
     * @param array $context The context array
     * @return string The generated answer
     * @throws HybridRAGException If generating the answer fails
     */
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

    /**
     * Merge vector and graph contexts.
     *
     * @param array $vectorContext The vector context
     * @param array $graphContext The graph context
     * @return array The merged context
     */
    private function mergeContext(array $vectorContext, array $graphContext): array
    {
        $mergedContext = [];
        $vectorScore = $this->config->hybridrag['vector_weight'];
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

    /**
     * Find existing context in the merged context array.
     *
     * @param array $mergedContext The merged context array
     * @param string $id The ID to search for
     * @return int|false The index of the found context or false if not found
     */
    private function findExistingContext(array $mergedContext, string $id): int|false
    {
        foreach ($mergedContext as $index => $item) {
            if (isset($item['metadata']['id']) && $item['metadata']['id'] === $id) {
                return $index;
            }
        }
        return false;
    }

    /**
     * Format context for the language model.
     *
     * @param array $context The context array
     * @return string The formatted context string
     */
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

    /**
     * Construct a prompt for the language model.
     *
     * @param string $query The query string
     * @param string $context The formatted context
     * @return string The constructed prompt
     */
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

    /**
     * Extract entities from the given content.
     *
     * @param string $content The content to extract entities from
     * @return array The extracted entities
     */
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

    /**
     * Train the NER classifier.
     */
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

    /**
     * Set the vector weight.
     *
     * @param float $weight The weight to set
     * @return self
     */
    public function setVectorWeight(float $weight): self
    {
        $this->config->set('hybridrag.vector_weight', $weight);
        return $this;
    }

    /**
     * Set the top K value.
     *
     * @param int $topK The top K value to set
     * @return self
     */
    public function setTopK(int $topK): self
    {
        $this->config->set('hybridrag.top_k', $topK);
        return $this;
    }

    /**
     * Set the max depth.
     *
     * @param int $maxDepth The max depth to set
     * @return self
     */
    public function setMaxDepth(int $maxDepth): self
    {
        $this->config->set('hybridrag.max_depth', $maxDepth);
        return $this;
    }

    /**
     * Incorporate topics into the context.
     *
     * @param array $context The context array
     * @param array $queryTopics The query topics
     * @return array The updated context array
     */
    private function incorporateTopics(array $context, array $queryTopics): array
    {
        foreach ($context as &$item) {
            $topicSimilarity = $this->calculateCosineSimilarity($queryTopics, $item['metadata']['topics'] ?? []);
            $item['score'] *= (1 + $topicSimilarity);
        }
        return $context;
    }

    /**
     * Incorporate sentiment into the context.
     *
     * @param array $context The context array
     * @param float $querySentiment The query sentiment
     * @return array The updated context array
     */
    private function incorporateSentiment(array $context, float $querySentiment): array
    {
        foreach ($context as &$item) {
            $sentimentDifference = abs($querySentiment - ($item['metadata']['sentiment'] ?? 0));
            $item['score'] *= (1 - $sentimentDifference);
        }
        return $context;
    }

    /**
     * Calculate cosine similarity between two vectors.
     *
     * @param array $a The first vector
     * @param array $b The second vector
     * @return float The cosine similarity
     */
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

        return ($magnitudeA * $magnitudeB) != 0 ? $dotProduct / ($magnitudeA * $magnitudeB) : 0.0;
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
        return $this->activeLearner->selectSamples($unlabeledSamples, $numSamples);
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
    public function evaluatePerformance(string $query, string $answer, array $context, array $relevantContext): array
    {
        return $this->evaluationMetrics->generateEvaluationReport($query, $answer, $context, $relevantContext);
    }

    /**
     * Add an image to the system.
     *
     * @param string $id The image ID
     * @param string $filePath The file path of the image
     * @param array $metadata Additional metadata
     */
    public function addImage(string $id, string $filePath, array $metadata = []): void
    {
        $this->vectorRAG->addImage($id, $filePath, $metadata);
        $this->graphRAG->addImage($id, $filePath, $metadata);
    }

    /**
     * Add an audio file to the system.
     *
     * @param string $id The audio ID
     * @param string $filePath The file path of the audio
     * @param array $metadata Additional metadata
     */
    public function addAudio(string $id, string $filePath, array $metadata = []): void
    {
        $this->vectorRAG->addAudio($id, $filePath, $metadata);
        $this->graphRAG->addAudio($id, $filePath, $metadata);
    }

    /**
     * Add a video file to the system.
     *
     * @param string $id The video ID
     * @param string $filePath The file path of the video
     * @param array $metadata Additional metadata
     */
    public function addVideo(string $id, string $filePath, array $metadata = []): void
    {
        $this->vectorRAG->addVideo($id, $filePath, $metadata);
        $this->graphRAG->addVideo($id, $filePath, $metadata);
    }
}
