<?php

declare(strict_types=1);

use GoldenPathDigital\LaravelAscend\Config\Config;

beforeEach(function () {
    Config::reset();
});

afterEach(function () {
    Config::reset();
});

test('config loads defaults when no file exists', function () {
    Config::setConfigPath('/nonexistent/path/config.php');
    
    $config = Config::all();
    
    expect($config)->toBeArray()
        ->toHaveKey('server')
        ->toHaveKey('knowledge_base')
        ->toHaveKey('documentation')
        ->toHaveKey('analysis');
});

test('config get returns default value for missing key', function () {
    $value = Config::get('nonexistent.key', 'default');
    
    expect($value)->toBe('default');
});

test('config get returns nested values with dot notation', function () {
    $host = Config::get('server.host');
    
    expect($host)->toBe('127.0.0.1');
});

test('config get returns null for missing key without default', function () {
    $value = Config::get('nonexistent.key');
    
    expect($value)->toBeNull();
});

test('config has server settings', function () {
    $config = Config::all();
    
    expect($config['server'])->toBeArray()
        ->toHaveKey('host')
        ->toHaveKey('port')
        ->toHaveKey('protocol');
    
    expect($config['server']['host'])->toBe('127.0.0.1');
    expect($config['server']['port'])->toBe(8765);
    expect($config['server']['protocol'])->toBe('websocket');
});

test('config has knowledge base settings', function () {
    $config = Config::all();
    
    expect($config['knowledge_base'])->toBeArray()
        ->toHaveKey('path')
        ->toHaveKey('cache_enabled')
        ->toHaveKey('cache_ttl');
    
    expect($config['knowledge_base']['cache_enabled'])->toBeTrue();
    expect($config['knowledge_base']['cache_ttl'])->toBe(86400);
});

test('config has documentation settings', function () {
    $config = Config::all();
    
    expect($config['documentation'])->toBeArray()
        ->toHaveKey('sources')
        ->toHaveKey('auto_update')
        ->toHaveKey('update_interval');
});

test('config has analysis settings', function () {
    $config = Config::all();
    
    expect($config['analysis'])->toBeArray()
        ->toHaveKey('exclude_paths')
        ->toHaveKey('max_file_size')
        ->toHaveKey('max_scan_depth')
        ->toHaveKey('timeout');
    
    expect($config['analysis']['exclude_paths'])->toBeArray();
    expect($config['analysis']['max_file_size'])->toBeInt();
});

test('config has breaking changes settings', function () {
    $config = Config::all();
    
    expect($config['breaking_changes'])->toBeArray()
        ->toHaveKey('severity_levels')
        ->toHaveKey('include_deprecations')
        ->toHaveKey('group_by');
});

test('config has tools settings', function () {
    $config = Config::all();
    
    expect($config['tools'])->toBeArray()
        ->toHaveKey('rate_limit')
        ->toHaveKey('enable_all')
        ->toHaveKey('disabled_tools');
});

test('config has logging settings', function () {
    $config = Config::all();
    
    expect($config['logging'])->toBeArray()
        ->toHaveKey('enabled')
        ->toHaveKey('level')
        ->toHaveKey('channel');
});

test('config has security settings', function () {
    $config = Config::all();
    
    expect($config['security'])->toBeArray()
        ->toHaveKey('require_auth')
        ->toHaveKey('auth_token')
        ->toHaveKey('allowed_origins')
        ->toHaveKey('read_only');
    
    expect($config['security']['read_only'])->toBeTrue();
});

test('config has performance settings', function () {
    $config = Config::all();
    
    expect($config['performance'])->toBeArray()
        ->toHaveKey('concurrent_requests')
        ->toHaveKey('memory_limit')
        ->toHaveKey('enable_caching');
});

test('config has upgrade settings', function () {
    $config = Config::all();
    
    expect($config['upgrade'])->toBeArray()
        ->toHaveKey('allow_version_skipping')
        ->toHaveKey('recommend_incremental')
        ->toHaveKey('php_version_check')
        ->toHaveKey('package_compatibility_check');
    
    expect($config['upgrade']['recommend_incremental'])->toBeTrue();
});

test('config reset clears cached config', function () {
    Config::all(); // Load config
    Config::reset();
    
    // After reset, it should reload
    $config = Config::all();
    
    expect($config)->toBeArray();
});

test('config handles deep nested dot notation', function () {
    $value = Config::get('documentation.sources.github_org');
    
    expect($value)->toBe('laravel');
});

test('config returns array for partial dot notation', function () {
    $sources = Config::get('documentation.sources');
    
    expect($sources)->toBeArray()
        ->toHaveKey('github_org')
        ->toHaveKey('repo_pattern');
});
