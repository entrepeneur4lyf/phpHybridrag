<?php

namespace Tests\VectorRAG;

use PHPUnit\Framework\TestCase;
use HybridRAG\VectorRAG\VectorRAG;
use HybridRAG\VectorDatabase\VectorDatabaseInterface;
use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\LanguageModel\LanguageModelInterface;
use HybridRAG\Logging\Logger;
use HybridRAG\Exception\HybridRAGException;

class VectorRAGTest extends TestCase
{
    private $vectorDB;
    private $embedding;
    private $languageModel;
    private $logger;
    private $vectorRAG;

    protected function setUp(): void
    {
        $this->vectorDB = $this->createMock(VectorDatabaseInterface::class);
        $this->embedding = $this->createMock(EmbeddingInterface::class);
        $this->languageModel = $this->createMock(LanguageModelInterface::class);
        $this->logger = $this->createMock(Logger::class);

        $this->vectorRAG = new VectorRAG(
            $this->vectorDB,
            $this->embedding,
            $this->languageModel,
            $this->logger
        );
    }

    public function testAddDocument()
    {
        $id = 'test_id';
        $content = 'Test content';
        $metadata = ['key' => 'value'];
        $vector = [0.1, 0.2, 0.3];

        $this->embedding->expects($this->once())
            ->method('embed')
            ->with($content)
            ->willReturn($vector);

        $this->vectorDB->expects($this->once())
            ->method('insert')
            ->with($id, $vector, $metadata);

        $this->vectorRAG->addDocument($id, $content, $metadata);
    }

    public function testRetrieveContext()
    {
        $query = 'Test query';
        $topK = 5;
        $queryVector = [0.1, 0.2, 0.3];
        $results = [
            ['id' => '1', 'content' => 'Result 1', 'vector' => [0.2, 0.3, 0.4]],
            ['id' => '2', 'content' => 'Result 2', 'vector' => [0.3, 0.4, 0.5]],
        ];

        $this->embedding->expects($this->once())
            ->method('embed')
            ->with($query)
            ->willReturn($queryVector);

        $this->vectorDB->expects($this->once())
            ->method('query')
            ->with($queryVector, $topK)
            ->willReturn($results);

        $context = $this->vectorRAG->retrieveContext($query, $topK);

        $this->assertCount(2, $context);
        $this->assertEquals('Result 1', $context[0]['content']);
        $this->assertEquals('Result 2', $context[1]['content']);
    }

    public function testGenerateAnswer()
    {
        $query = 'Test query';
        $context = [['content' => 'Context 1'], ['content' => 'Context 2']];
        $expectedAnswer = 'Generated answer';

        $this->languageModel->expects($this->once())
            ->method('generateResponse')
            ->with($query, $context)
            ->willReturn($expectedAnswer);

        $answer = $this->vectorRAG->generateAnswer($query, $context);

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function testAddDocumentWithEmptyContent()
    {
        $this->expectException(HybridRAGException::class);
        $this->expectExceptionMessage("Document content cannot be empty");

        $this->vectorRAG->addDocument('test_id', '', []);
    }

    public function testRetrieveContextWithNoResults()
    {
        $query = 'Test query';
        $topK = 5;
        $queryVector = [0.1, 0.2, 0.3];

        $this->embedding->expects($this->once())
            ->method('embed')
            ->with($query)
            ->willReturn($queryVector);

        $this->vectorDB->expects($this->once())
            ->method('query')
            ->with($queryVector, $topK)
            ->willReturn([]);

        $context = $this->vectorRAG->retrieveContext($query, $topK);

        $this->assertEmpty($context);
    }

    public function testGenerateAnswerWithEmptyContext()
    {
        $query = 'Test query';
        $context = [];
        $expectedAnswer = 'I don\'t have enough information to answer that question.';

        $this->languageModel->expects($this->once())
            ->method('generateResponse')
            ->with($query, $context)
            ->willReturn($expectedAnswer);

        $answer = $this->vectorRAG->generateAnswer($query, $context);

        $this->assertEquals($expectedAnswer, $answer);
    }
}