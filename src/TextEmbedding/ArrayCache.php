<?php

declare(strict_types=1);

namespace HybridRAG\TextEmbedding;

use Psr\SimpleCache\CacheInterface;

class ArrayCache implements CacheInterface
{
    private array $cache = [];
    private array $expiration = [];

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->cache[$key];
        }
        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->cache[$key] = $value;
        if ($ttl !== null) {
            $this->expiration[$key] = time() + $ttl;
        }
        return true;
    }

    public function delete($key)
    {
        unset($this->cache[$key], $this->expiration[$key]);
        return true;
    }

    public function clear()
    {
        $this->cache = [];
        $this->expiration = [];
        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has($key)
    {
        if (!isset($this->cache[$key])) {
            return false;
        }
        if (isset($this->expiration[$key]) && $this->expiration[$key] < time()) {
            $this->delete($key);
            return false;
        }
        return true;
    }
}