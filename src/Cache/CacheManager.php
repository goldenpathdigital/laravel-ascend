<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Cache;

use GoldenPathDigital\LaravelAscend\Exceptions\CacheException;

final class CacheManager
{
    private const DEFAULT_TTL = 3600;
    private const DEFAULT_MAX_SIZE = 100;
    private const DEFAULT_MAX_VALUE_SIZE = 1048576; // 1MB

    /** @var array<string, string> Stores serialized values to avoid double serialization */
    private array $cache = [];

    /** @var array<string, int> */
    private array $timestamps = [];

    /** @var int Current memory usage estimate in bytes */
    private int $currentMemoryUsage = 0;

    private int $defaultTtl;
    private int $maxCacheSize;
    private int $maxValueSize;

    public function __construct(
        int $defaultTtl = self::DEFAULT_TTL,
        int $maxCacheSize = self::DEFAULT_MAX_SIZE,
        int $maxValueSize = self::DEFAULT_MAX_VALUE_SIZE
    ) {
        $this->defaultTtl = $defaultTtl;
        $this->maxCacheSize = $maxCacheSize;
        $this->maxValueSize = $maxValueSize;
    }

    /**
     * Store a value in the cache with optional TTL.
     *
     * @param string $key The cache key (alphanumeric with ._-: allowed, max 255 chars)
     * @param mixed $value The value to cache (will be serialized)
     * @param int|null $ttl Time-to-live in seconds (uses default if null)
     * @throws CacheException If key is invalid or value exceeds max size
     */
    public function set(string $key, $value, ?int $ttl = null): void
    {
        $this->validateKey($key);

        // Serialize once for validation and storage
        $serialized = serialize($value);
        $size = strlen($serialized);

        if ($size > $this->maxValueSize) {
            throw CacheException::valueTooLarge($key, $size, $this->maxValueSize);
        }

        // If updating existing key, account for old size
        if (isset($this->cache[$key])) {
            $this->currentMemoryUsage -= strlen($this->cache[$key]);
        }

        // Enforce cache size limits using LRU eviction
        while (
            (count($this->cache) >= $this->maxCacheSize && !isset($this->cache[$key])) ||
            ($this->currentMemoryUsage + $size > $this->maxValueSize * $this->maxCacheSize)
        ) {
            $this->evictOldest();
        }

        $this->cache[$key] = $serialized;
        $this->timestamps[$key] = time();
        $this->currentMemoryUsage += $size;
    }

    /**
     * Retrieve a value from the cache.
     *
     * @param string $key The cache key
     * @param mixed $default Default value if key doesn't exist or is expired
     * @return mixed The cached value or default
     */
    public function get(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        // Unserialize and return
        return unserialize($this->cache[$key]);
    }

    /**
     * Check if a cache key exists and is not expired.
     *
     * @param string $key The cache key to check
     * @return bool True if key exists and not expired, false otherwise
     */
    public function has(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        // Check if expired
        $age = time() - $this->timestamps[$key];
        if ($age > $this->defaultTtl) {
            $this->forget($key);
            return false;
        }

        return true;
    }

    /**
     * Remove a value from the cache.
     *
     * @param string $key The cache key to remove
     */
    public function forget(string $key): void
    {
        if (isset($this->cache[$key])) {
            $this->currentMemoryUsage -= strlen($this->cache[$key]);
        }
        unset($this->cache[$key], $this->timestamps[$key]);
    }

    /**
     * Clear all cached values.
     */
    public function clear(): void
    {
        $this->cache = [];
        $this->timestamps = [];
        $this->currentMemoryUsage = 0;
    }

    /**
     * Get a value from cache, or store the result of the callback if not present.
     *
     * @param string $key The cache key
     * @param callable(): mixed $callback Function to execute if cache miss
     * @param int|null $ttl Time-to-live in seconds (uses default if null)
     * @return mixed The cached value or callback result
     * @throws CacheException If key is invalid or value exceeds max size
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        if ($this->has($key)) {
            $value = $this->get($key);
            if ($value !== null) {
                return $value;
            }
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Get cache statistics
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return [
            'size' => count($this->cache),
            'max_size' => $this->maxCacheSize,
            'memory_usage' => $this->currentMemoryUsage,
            'memory_limit' => $this->maxValueSize * $this->maxCacheSize,
        ];
    }

    /**
     * Evict the oldest (least recently used) cache entry.
     * Uses array_search on min() for O(n) performance, but more efficient than manual iteration.
     */
    private function evictOldest(): void
    {
        if (empty($this->timestamps)) {
            return;
        }

        $oldestTime = min($this->timestamps);
        $oldestKey = array_search($oldestTime, $this->timestamps, true);

        if ($oldestKey !== false) {
            $this->forget((string) $oldestKey);
        }
    }

    private function validateKey(string $key): void
    {
        if ($key === '') {
            throw CacheException::invalidKey($key);
        }

        if (!preg_match('/^[a-zA-Z0-9_.\-:]+$/', $key)) {
            throw CacheException::invalidKey($key);
        }

        if (strlen($key) > 255) {
            throw CacheException::invalidKey($key);
        }
    }
}
