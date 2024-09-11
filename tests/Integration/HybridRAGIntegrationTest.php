<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use HybridRAG\HybridRAG\HybridRAG;
use HybridRAG\VectorRAG\VectorRAG;
use HybridRAG\GraphRAG\GraphRAG;
use HybridRAG\Reranker\HybridReranker;
use HybridRAG\LanguageModel\OpenAILanguageModel;
use HybridRAG\Config\Configuration;
use HybridRAG\VectorDatabase\ChromaDB;
use HybridRAG\KnowledgeGraph\ArangoDBManager;
use HybridRAG\TextEmbedding\OpenAIEmbedding;
use HybridRAG\Logging\Logger;

class HybridRAGIntegrationTest extends TestCase
{
    private HybridRAG $hybridRAG;

    protected function setUp(): void
    {
        $config = new Configuration('config/config.php');
        $logger = new Logger('test_logger', 'path/to/test.log');

        $vectorDB = new ChromaDB($config->get('chromadb'));
        $graphDB = new ArangoDBManager($config->get('arangodb'));
        $embedding = new OpenAIEmbedding($config->get('openai.api_key'));
        $languageModel = new OpenAILanguageModel($config->get('openai.api_key'), $config->get('openai.language_model'), $logger);

        $vectorRAG = new VectorRAG($vectorDB, $embedding, $languageModel, $logger);
        $graphRAG = new GraphRAG($graphDB, $embedding, $languageModel, $logger);
        $reranker = new HybridReranker($embedding, $vectorDB, $logger);

        $this->hybridRAG = new HybridRAG($vectorRAG, $graphRAG, $reranker, $languageModel, $config);
    }

    public function testEndToEndWorkflow()
    {
        // Add a document
        $docId = 'test_doc_1';
        $content = 'This is a test document about artificial intelligence.';
        $metadata = ['source' => 'test', 'date' => '2023-06-01'];
        
        $this->hybridRAG->addDocument($docId, $content, $metadata);

        // Retrieve context and generate answer
        $query = 'What is the document about?';
        $context = $this->hybridRAG->retrieveContext($query);
        
        $this->assertNotEmpty($context);
        $this->assertArrayHasKey('content', $context[0]);
        $this->assertStringContainsString('artificial intelligence', $context[0]['content']);

        $answer = $this->hybridRAG->generateAnswer($query, $context);
        
        $this->assertNotEmpty($answer);
        $this->assertStringContainsString('artificial intelligence', $answer);
    }

    public function testErrorHandling()
    {
        $this->expectException(HybridRAGException::class);
        
        // Try to retrieve context with an empty query
        $this->hybridRAG->retrieveContext('');
    }

    protected function tearDown(): void
    {
        // Clean up test data
        // Note: Implement methods to clean up test data from your databases
    }
}
