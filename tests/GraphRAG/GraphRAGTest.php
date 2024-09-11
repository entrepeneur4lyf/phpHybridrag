<?php

namespace Tests\GraphRAG;

use PHPUnit\Framework\TestCase;
use HybridRAG\GraphRAG\GraphRAG;
use HybridRAG\KnowledgeGraph\KnowledgeGraphBuilder;
use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\LanguageModel\LanguageModelInterface;
use HybridRAG\Logging\Logger;
use HybridRAG\Exception\HybridRAGException;

class GraphRAGTest extends TestCase
{
    private $kg;
    private $embedding;
    private $languageModel;
    private $logger;
    private $graphRAG;

    protected function setUp(): void
    {
        $this->kg = $this->createMock(KnowledgeGraphBuilder::class);
        $this->embedding = $this->createMock(EmbeddingInterface::class);
        $this->languageModel = $this->createMock(LanguageModelInterface::class);
        $this->logger = $this->createMock(Logger::class);

        $this->graphRAG = new GraphRAG(
            $this->kg,
            $this->embedding,
            $this->languageModel,
            $this->logger
        );
    }

    public function testAddEntity()
    {
        $id = 'test_id';
        $content = 'Test content';
        $metadata = ['key' => 'value'];
        $vector = [0.1, 0.2, 0.3];

        $this->embedding->expects($this->once())
            ->method('embed')
            ->with($content)
            ->willReturn($vector);

        $this->kg->expects($this->once())
            ->method('addEntity')
            ->willReturn($id);

        $result = $this->graphRAG->addEntity($id, $content, $metadata);

        $this->assertEquals($id, $result);
    }

    public function testAddEntityWithEmptyContent()
    {
        $this->expectException(HybridRAGException::class);
        $this->expectExceptionMessage("Entity content cannot be empty");

        $this->graphRAG->addEntity('test_id', '', []);
    }

    public function testAddRelationship()
    {
        $fromId = 'from_id';
        $toId = 'to_id';
        $type = 'TEST_RELATION';
        $attributes = ['key' => 'value'];

        $this->kg->expects($this->exactly(2))
            ->method('getEntity')
            ->willReturnCallback(function ($id) use ($fromId, $toId) {
                if ($id === $fromId) {
                    return new \HybridRAG\KnowledgeGraph\Entity('entities', ['id' => $fromId]);
                } elseif ($id === $toId) {
                    return new \HybridRAG\KnowledgeGraph\Entity('entities', ['id' => $toId]);
                }
                return null;
            });

        $this->kg->expects($this->once())
            ->method('addRelationship')
            ->willReturn('relation_id');

        $result = $this->graphRAG->addRelationship($fromId, $toId, $type, $attributes);

        $this->assertEquals('relation_id', $result);
    }

    public function testAddRelationshipWithNonExistentEntities()
    {
        $fromId = 'non_existent_from';
        $toId = 'non_existent_to';
        $type = 'TEST_RELATION';

        $this->kg->expects($this->exactly(2))
            ->method('getEntity')
            ->willReturn(null);

        $this->expectException(HybridRAGException::class);
        $this->expectExceptionMessage("One or both entities do not exist");

        $this->graphRAG->addRelationship($fromId, $toId, $type, []);
    }

    public function testRetrieveContext()
    {
        $query = 'Test query';
        $maxDepth = 2;
        $queryVector = [0.1, 0.2, 0.3];
        $entities = [['id' => '1', 'name' => 'Entity 1', 'similarity' => 0.9]];
        $subgraph = [
            ['vertex' => ['_id' => '1', 'content' => 'Entity 1 content']],
            ['edge' => ['_id' => 'e1', '_from' => '1', '_to' => '2', 'type' => 'RELATED_TO']]
        ];

        $this->embedding->expects($this->once())
            ->method('embed')
            ->with($query)
            ->willReturn($queryVector);

        $this->kg->expects($this->once())
            ->method('query')
            ->willReturn($entities);

        $this->kg->expects($this->once())
            ->method('depthFirstSearch')
            ->willReturn($subgraph);

        $context = $this->graphRAG->retrieveContext($query, $maxDepth);

        $this->assertCount(2, $context);
        $this->assertEquals('entity', $context[0]['type']);
        $this->assertEquals('relationship', $context[1]['type']);
    }

    public function testRetrieveContextWithNoEntities()
    {
        $query = 'Test query';
        $maxDepth = 2;
        $queryVector = [0.1, 0.2, 0.3];

        $this->embedding->expects($this->once())
            ->method('embed')
            ->with($query)
            ->willReturn($queryVector);

        $this->kg->expects($this->once())
            ->method('query')
            ->willReturn([]);

        $context = $this->graphRAG->retrieveContext($query, $maxDepth);

        $this->assertEmpty($context);
    }

    public function testGenerateAnswer()
    {
        $query = 'Test query';
        $context = [
            ['type' => 'entity', 'content' => 'Entity 1', 'metadata' => []],
            ['type' => 'relationship', 'relationshipType' => 'RELATED_TO', 'from' => '1', 'to' => '2', 'metadata' => []]
        ];
        $expectedAnswer = 'Generated answer';

        $this->languageModel->expects($this->once())
            ->method('generateResponse')
            ->willReturn($expectedAnswer);

        $answer = $this->graphRAG->generateAnswer($query, $context);

        $this->assertEquals($expectedAnswer, $answer);
    }
}
