<?php

namespace Tests\HybridRAG;

use PHPUnit\Framework\TestCase;
use HybridRAG\HybridRAG\HybridRAG;
use HybridRAG\VectorRAG\VectorRAGInterface;
use HybridRAG\GraphRAG\GraphRAGInterface;
use HybridRAG\Reranker\RerankerInterface;
use HybridRAG\LanguageModel\LanguageModelInterface;
use HybridRAG\Config\Configuration;
use HybridRAG\Exception\HybridRAGException;

class HybridRAGTest extends TestCase
{
    private $vectorRAG;
    private $graphRAG;
    private $reranker;
    private $languageModel;
    private $config;
    private $hybridRAG;

    protected function setUp(): void
    {
        $this->vectorRAG = $this->createMock(VectorRAGInterface::class);
        $this->graphRAG = $this->createMock(GraphRAGInterface::class);
        $this->reranker = $this->createMock(RerankerInterface::class);
        $this->languageModel = $this->createMock(LanguageModelInterface::class);
        $this->config = $this->createMock(Configuration::class);

        $this->config->method('get')
            ->willReturnMap([
                ['hybridrag.top_k', null, 5],
                ['hybridrag.max_depth', null, 2],
                ['hybridrag.vector_weight', null, 0.5],
                ['reranker.top_k', null, 10],
            ]);

        $this->hybridRAG = new HybridRAG(
            $this->vectorRAG,
            $this->graphRAG,
            $this->reranker,
            $this->languageModel,
            $this->config
        );
    }

    public function testAddDocument()
    {
        $id = 'test_id';
        $content = 'Test content';
        $metadata = ['key' => 'value'];

        $this->vectorRAG->expects($this->once())
            ->method('addDocument')
            ->with($id, $content, $metadata);

        $this->graphRAG->expects($this->once())
            ->method('addEntity')
            ->with($id, $content, $metadata);

        $this->hybridRAG->addDocument($id, $content, $metadata);
    }

    public function testRetrieveContext()
    {
        $query = 'Test query';
        $vectorContext = [['content' => 'Vector Result', 'score' => 0.8]];
        $graphContext = [['content' => 'Graph Result', 'score' => 0.7]];
        $rerankedContext = [
            ['content' => 'Vector Result', 'score' => 0.9],
            ['content' => 'Graph Result', 'score' => 0.8]
        ];

        $this->vectorRAG->expects($this->once())
            ->method('retrieveContext')
            ->with($query, 5)
            ->willReturn($vectorContext);

        $this->graphRAG->expects($this->once())
            ->method('retrieveContext')
            ->with($query, 2)
            ->willReturn($graphContext);

        $this->reranker->expects($this->once())
            ->method('rerank')
            ->with($query, $this->anything(), 10)
            ->willReturn($rerankedContext);

        $result = $this->hybridRAG->retrieveContext($query);

        $this->assertEquals($rerankedContext, $result);
    }

    public function testGenerateAnswer()
    {
        $query = 'Test query';
        $context = [
            ['content' => 'Context 1', 'score' => 0.9],
            ['content' => 'Context 2', 'score' => 0.8]
        ];
        $expectedAnswer = 'Generated answer';

        $this->languageModel->expects($this->once())
            ->method('generateResponse')
            ->willReturn($expectedAnswer);

        $answer = $this->hybridRAG->generateAnswer($query, $context);

        $this->assertEquals($expectedAnswer, $answer);
    }

    public function testRetrieveContextWithEmptyQuery()
    {
        $this->expectException(HybridRAGException::class);
        $this->expectExceptionMessage("Query cannot be empty");

        $this->hybridRAG->retrieveContext('');
    }

    public function testGenerateAnswerWithEmptyContext()
    {
        $query = 'Test query';
        $context = [];
        $expectedAnswer = "I don't have enough context to answer that question.";

        $this->languageModel->expects($this->once())
            ->method('generateResponse')
            ->willReturn($expectedAnswer);

        $answer = $this->hybridRAG->generateAnswer($query, $context);

        $this->assertEquals($expectedAnswer, $answer);
    }
}