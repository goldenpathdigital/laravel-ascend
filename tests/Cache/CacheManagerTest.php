<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Cache\CacheManager;
use GoldenPathDigital\LaravelAscend\Exceptions\CacheException;

test('cache manager stores and retrieves values', function () {
    $cache = new CacheManager();
    
    $cache->set('test-key', 'test-value');
    
    expect($cache->get('test-key'))->toBe('test-value');
    expect($cache->has('test-key'))->toBeTrue();
});

test('cache manager returns default for missing keys', function () {
    $cache = new CacheManager();
    
    expect($cache->get('nonexistent', 'default'))->toBe('default');
    expect($cache->has('nonexistent'))->toBeFalse();
});

test('cache manager validates key format', function () {
    $cache = new CacheManager();
    
    expect(fn() => $cache->set('invalid key!', 'value'))
        ->toThrow(CacheException::class, 'Invalid cache key');
});

test('cache manager rejects empty keys', function () {
    $cache = new CacheManager();
    
    expect(fn() => $cache->set('', 'value'))
        ->toThrow(CacheException::class);
});

test('cache manager rejects oversized keys', function () {
    $cache = new CacheManager();
    $longKey = str_repeat('a', 256);
    
    expect(fn() => $cache->set($longKey, 'value'))
        ->toThrow(CacheException::class);
});

test('cache manager validates value size', function () {
    $cache = new CacheManager(3600, 100, 100); // 100 byte limit
    $largeValue = str_repeat('x', 200);
    
    expect(fn() => $cache->set('key', $largeValue))
        ->toThrow(CacheException::class, 'too large');
});

test('cache manager forgets values', function () {
    $cache = new CacheManager();
    $cache->set('key', 'value');
    
    expect($cache->has('key'))->toBeTrue();
    
    $cache->forget('key');
    
    expect($cache->has('key'))->toBeFalse();
});

test('cache manager clears all values', function () {
    $cache = new CacheManager();
    $cache->set('key1', 'value1');
    $cache->set('key2', 'value2');
    
    expect($cache->has('key1'))->toBeTrue();
    expect($cache->has('key2'))->toBeTrue();
    
    $cache->clear();
    
    expect($cache->has('key1'))->toBeFalse();
    expect($cache->has('key2'))->toBeFalse();
});

test('cache manager remembers values from callback', function () {
    $cache = new CacheManager();
    $callCount = 0;
    
    $callback = function () use (&$callCount) {
        $callCount++;
        return 'computed-value';
    };
    
    $result1 = $cache->remember('key', $callback);
    $result2 = $cache->remember('key', $callback);
    
    expect($result1)->toBe('computed-value');
    expect($result2)->toBe('computed-value');
    expect($callCount)->toBe(1); // Callback only called once
});

test('cache manager evicts oldest entry when full', function () {
    $cache = new CacheManager(3600, 2); // Max 2 entries
    
    $cache->set('key1', 'value1');
    sleep(1); // Ensure different timestamps
    $cache->set('key2', 'value2');
    sleep(1);
    $cache->set('key3', 'value3'); // Should evict key1
    
    expect($cache->has('key1'))->toBeFalse();
    expect($cache->has('key2'))->toBeTrue();
    expect($cache->has('key3'))->toBeTrue();
});

test('cache manager provides statistics', function () {
    $cache = new CacheManager();
    $cache->set('key1', 'value1');
    $cache->set('key2', 'value2');
    
    $stats = $cache->stats();
    
    expect($stats)->toHaveKey('size');
    expect($stats)->toHaveKey('max_size');
    expect($stats)->toHaveKey('memory_usage');
    expect($stats['size'])->toBe(2);
});

test('cache manager accepts valid key patterns', function () {
    $cache = new CacheManager();
    
    // Valid keys
    $validKeys = [
        'simple',
        'with_underscore',
        'with-dash',
        'with.dot',
        'with:colon',
        'mixed_all-of.these:together',
        'CamelCase123',
    ];
    
    foreach ($validKeys as $key) {
        $cache->set($key, 'value');
        expect($cache->has($key))->toBeTrue();
    }
});

test('cache manager updates timestamp on set', function () {
    $cache = new CacheManager(2); // 2 second TTL
    
    $cache->set('key', 'value1');
    sleep(1);
    $cache->set('key', 'value2'); // Update resets timestamp
    sleep(1);
    
    // Should still be valid (1 second after update, 2 seconds total)
    expect($cache->get('key'))->toBe('value2');
});
