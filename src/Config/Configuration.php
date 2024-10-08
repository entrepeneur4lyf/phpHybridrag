<?php

declare(strict_types=1);

namespace HybridRAG\Config;

use HybridRAG\Exception\HybridRAGException;

/**
 * Class Configuration
 *
 * This class handles the configuration management for the HybridRAG system.
 */
class Configuration
{
    private array $config;
    private string $configPath;

    /**
     * Configuration constructor.
     *
     * @param string $configPath The path to the configuration file
     * @throws HybridRAGException If the configuration file is not found
     */
    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
        $this->loadConfig();
        $this->initializeProperties();
    }

    /**
     * Initialize properties from the configuration array.
     */
    private function initializeProperties(): void
    {
        foreach ($this->config as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Magic method to get a configuration property.
     *
     * @param string $name The property name
     * @return mixed The property value
     * @throws HybridRAGException If the property does not exist
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        throw new HybridRAGException("Configuration property '{$name}' does not exist.");
    }

    /**
     * Magic method to set a configuration property.
     *
     * @param string $name The property name
     * @param mixed $value The property value
     * @throws HybridRAGException If the property does not exist
     */
    public function __set(string $name, $value): void
    {
        if (property_exists($this, $name)) {
            $this->{$name} = $value;
            return;
        }

        throw new HybridRAGException("Configuration property '{$name}' does not exist.");
    }

    /**
     * Load the configuration from the given path.
     *
     * @throws HybridRAGException If the configuration file is not found
     */
    private function loadConfig(): void
    {
        if (!file_exists($this->configPath)) {
            throw new HybridRAGException("Configuration file not found: {$this->configPath}");
        }

        $config = include $this->configPath;
        if (!is_array($config)) {
            throw new HybridRAGException("Configuration file does not return an array");
        }
        $this->config = $this->replaceEnvVariables($config);
    }

    /**
     * Replace environment variables in the configuration.
     *
     * @param array $config The configuration array
     * @return array The configuration with environment variables replaced
     */
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

    /**
     * Get a configuration value by key.
     *
     * @param string $key The configuration key
     * @param mixed $default The default value if the key is not found
     * @return mixed The configuration value
     */
    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set a configuration value.
     *
     * @param string $key The configuration key
     * @param mixed $value The value to set
     */
    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * Save the current configuration to the file.
     *
     * @throws HybridRAGException If the file format is not supported
     */
    public function save(): void
    {
        $extension = pathinfo($this->configPath, PATHINFO_EXTENSION);
        
        if ($extension === 'json') {
            file_put_contents($this->configPath, json_encode($this->config, JSON_PRETTY_PRINT));
        } else {
            throw new HybridRAGException("Unsupported configuration file format: $extension");
        }
    }

    /**
     * Get the entire configuration as an array.
     *
     * @return array The configuration array
     */
    public function toArray(): array
    {
        return $this->config;
    }

    /**
     * Get a nested value from an array using an array of keys.
     *
     * @param array $array The array to search in
     * @param array $keys The keys to traverse
     * @param mixed $default The default value if the key is not found
     * @return mixed The nested value or the default
     */
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

    /**
     * Set a nested value in an array using an array of keys.
     *
     * @param array $array The array to modify
     * @param array $keys The keys to traverse
     * @param mixed $value The value to set
     */
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
