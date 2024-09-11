<?php

namespace Tests\Reranker;

use PHPUnit\Framework\TestCase;
use HybridRAG\Reranker\HybridReranker;
use HybridRAG\TextEmbedding\EmbeddingInterface;
use HybridRAG\VectorDatabase\VectorDatabaseInterface;
use HybridRAG\Logging\Logger;
use Psr\SimpleCache\CacheInterface;
use HybridRAG\Exception\HybridRAGException;

class HybridRerankerTest extends TestCase
{
    private $embedding;
    private $vectorDB;
    private $cache;
    private $logger;
    private $reranker;

    protected function setUp(): void
    {
        $this->embedding = $this->createMock(EmbeddingInterface::class);
        $this->vectorDB = $this->createMock(VectorDatabaseInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(Logger::class);

        $this->reranker = new HybridReranker(
            $this->embedding,
            $this->vectorDB,
            $this->cache,
            $this->logger
        );
    }

    public function testRerank()
    {
        $query = 'Test query';
        $results = [
            ['id' => '1', 'content' => 'Result 1', 'score' => 0.8],
            ['id' => '2', 'content' => 'Result 2', 'score' => 0.7],
        ];
        $topK = 2;

        $this->embedding->expects($this->once())
            ->method('embed')
            ->with($query)
            ->willReturn([0.1, 0.2, 0.3]);

        $this->cache->expects($this->once())
            ->method('has')
            ->willReturn(false);

        $this->cache->expects($this->once())
            ->method('set');

        $rerankedResults = $this->reranker->rerank($query, $results, $topK);

        $this->assertCount(2, $rerankedResults);
        $this->assertArrayHasKey('combined_score', $rerankedResults[0]);
        $this->assertArrayHasKey('combined_score', $rerankedResults[1]);
    }

    public function testRerankWithCachedResults()
    {
        $query = 'Test query';
        $results = [
            ['id' => '1', 'content' => 'Result 1', 'score' => 0.8],
            ['id' => '2', 'content' => 'Result 2', 'score' => 0.7],
        ];
        $topK = 2;
        $cachedResults = [
            ['id' => '1', 'content' => 'Result 1', 'combined_score' => 0.9],
            ['id' => '2', 'content' => 'Result 2', 'combined_score' => 0.8],
        ];

        $this->cache->expects($this->once())
            ->method('has')
            ->willReturn(true);

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn($cachedResults);

        $rerankedResults = $this->reranker->rerank($query, $results, $topK);

        $this->assertEquals($cachedResults, $rerankedResults);
    }

    public function testRerankWithEmptyResults()
    {
        $query = 'Test query';
        $results = [];
        $topK = 2;

        $rerankedResults = $this->reranker->rerank($query, $results, $topK);

        $this->assertEmpty($rerankedResults);
    }

    public function testRerankWithInvalidTopK()
    {
        $this->expectException(HybridRAGException::class);

        $query = 'Test query';
        $results = [
            ['id' => '1', 'content' => 'Result 1', 'score' => 0.8],
            ['id' => '2', 'content' => 'Result 2', 'score' => 0.7],
        ];
        $topK = 0;

        $this->reranker->rerank($query, $results, $topK);
    }
}