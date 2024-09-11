<?php

namespace Tests\Config;

use PHPUnit\Framework\TestCase;
use HybridRAG\Config\Configuration;
use HybridRAG\Exception\HybridRAGException;

class ConfigurationTest extends TestCase
{
    private $configPath;
    private $configuration;

    protected function setUp(): void
    {
        $this->configPath = __DIR__ . '/../../config/config.php';
        $this->configuration = new Configuration($this->configPath);
    }


    public function testGet()
    {
        $this->assertEquals('value1', $this->configuration->get('test.key1'));
        $this->assertEquals('subvalue1', $this->configuration->get('test.key2.subkey1'));
        $this->assertNull($this->configuration->get('nonexistent.key'));
        $this->assertEquals('default', $this->configuration->get('nonexistent.key', 'default'));
    }

    public function testSet()
    {
        $this->configuration->set('test.key3', 'value3');
        $this->assertEquals('value3', $this->configuration->get('test.key3'));

        $this->configuration->set('test.key2.subkey3', 'subvalue3');
        $this->assertEquals('subvalue3', $this->configuration->get('test.key2.subkey3'));
    }


    public function testToArray()
    {
        $configArray = $this->configuration->toArray();
        $this->assertIsArray($configArray);
        $this->assertArrayHasKey('test', $configArray);
        $this->assertEquals('value1', $configArray['test']['key1']);
    }

    public function testLoadInvalidConfigFile()
    {
        $this->expectException(HybridRAGException::class);
        new Configuration('nonexistent.php');
    }
}
