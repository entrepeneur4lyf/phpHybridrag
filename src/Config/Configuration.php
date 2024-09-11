<?php

declare(strict_types=1);

namespace HybridRAG\Config;

use Symfony\Component\Yaml\Yaml;
use HybridRAG\Exception\HybridRAGException;

class Configuration
{
    private array $config;

    public function __construct(string $configPath)
    {
        $this->loadConfig($configPath);
    }

    private function loadConfig(string $configPath): void
    {
        if (!file_exists($configPath)) {
            throw new HybridRAGException("Configuration file not found: $configPath");
        }

        $config = Yaml::parseFile($configPath);
        $this->config = $this->replaceEnvVariables($config);
    }

    private function replaceEnvVariables(array $config): array
    {
        array_walk_recursive($config, function (&$value) {
            if (is_string($value) && preg_match('/^\${(.+)}$/', $value, $matches)) {
                $envVar = $matches[1];
                $value = getenv($envVar) ?: $value;
            }
        });

        return $config;
    }

    public function get(string $key, $default = null)
    {
        return $this->getNestedValue($this->config, explode('.', $key), $default);
    }

    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $this->setNestedValue($this->config, $keys, $value);
    }

    public function save(): void
    {
        $extension = pathinfo($this->configPath, PATHINFO_EXTENSION);
        
        if ($extension === 'yaml' || $extension === 'yml') {
            file_put_contents($this->configPath, Yaml::dump($this->config, 4, 2));
        } elseif ($extension === 'json') {
            file_put_contents($this->configPath, json_encode($this->config, JSON_PRETTY_PRINT));
        } else {
            throw new HybridRAGException("Unsupported configuration file format: $extension");
        }
    }

    public function toArray(): array
    {
        return $this->config;
    }

    private function getNestedValue(array $array, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (!isset($array[$key])) {
                return $default;
            }
            $array = $array[$key];
        }
        return $array;
    }

    private function setNestedValue(array &$array, array $keys, $value): void
    {
        $current = &$array;
        foreach ($keys as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        $current = $value;
    }
}