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
        $this->configPath = sys_get_temp_dir() . '/test_config.yaml';
        file_put_contents($this->configPath, "
            test:
                key1: value1
                key2: 
                    subkey1: subvalue1
                    subkey2: subvalue2
        ");
        $this->configuration = new Configuration($this->configPath);
    }

    protected function tearDown(): void
    {
        unlink($this->configPath);
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

    public function testSave()
    {
        $this->configuration->set('test.key3', 'value3');
        $this->configuration->save();

        $newConfiguration = new Configuration($this->configPath);
        $this->assertEquals('value3', $newConfiguration->get('test.key3'));
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
        new Configuration('nonexistent.file');
    }
}