# Laravel Ascend - Usage Guide

## Installation

```bash
composer require goldenpathdigital/laravel-ascend
```

The package will auto-register via Laravel's package discovery.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=ascend-config
```

This creates `config/ascend.php` with all available options.

## Quick Start

### 1. Register the MCP Server

```bash
php artisan ascend:register
```

This registers Ascend with your IDE's MCP configuration (VSCode, Cursor, etc.).

### 2. Start the Server

```bash
php artisan ascend:mcp
```

The server will start and be accessible via your IDE.

## Using the Analysis Tools

### Analyze Current Laravel Version

```php
use GoldenPathDigital\LaravelAscend\Server\AscendServer;

$server = AscendServer::createDefault();

// Get current project version
$result = $server->callTool('analyze_current_version', [
    'project_root' => base_path(),
]);

// Returns:
// [
//     'ok' => true,
//     'data' => [
//         'current_version' => '10.48.0',
//         'framework_info' => [...],
//     ],
// ]
```

### Get Upgrade Path

```php
$result = $server->callTool('get_upgrade_path', [
    'from_version' => '10',
    'to_version' => '11',
]);

// Returns detailed upgrade path with breaking changes
```

### Scan for Breaking Changes

```php
$result = $server->callTool('scan_breaking_changes', [
    'project_root' => base_path(),
    'target_version' => '11',
]);

// Returns list of detected breaking changes in your code
```

### Check PHP Compatibility

```php
$result = $server->callTool('check_php_compatibility', [
    'php_version' => PHP_VERSION,
    'target_laravel_version' => '11',
]);
```

## Knowledge Base Access

### Search Documentation

```php
$results = $server->searchKnowledgeBase('middleware', 10);

// Returns relevant documentation entries
```

### Get Breaking Change Details

```php
$change = $server->getBreakingChangeEntry('laravel-11', 'middleware-changes');

// Returns:
// [
//     'id' => 'middleware-changes',
//     'title' => 'Middleware Changes',
//     'severity' => 'high',
//     'description' => '...',
//     'data' => [...],
// ]
```

### Get Upgrade Pattern

```php
$pattern = $server->getPattern('factory-class-rewrite');

// Returns pattern with detection rules and fix suggestions
```

## Using with Facades

Laravel Ascend includes a built-in facade for convenient access:

```php
use GoldenPathDigital\LaravelAscend\Facades\Ascend;

// The facade is automatically registered by the service provider
$version = Ascend::callTool('analyze_current_version', [
    'project_root' => base_path(),
]);

// Search the knowledge base
$results = Ascend::searchKnowledgeBase('middleware changes');

// Get breaking changes
$change = Ascend::getBreakingChangeEntry('laravel-11', 'middleware-signature');
```

## Available Tools

### Analysis Tools
- `analyze_current_version` - Detect current Laravel version
- `analyze_dependencies` - Analyze package dependencies
- `check_php_compatibility` - Check PHP version requirements
- `get_upgrade_path` - Get upgrade path between versions
- `scan_breaking_changes` - Scan code for breaking changes

### Code Analysis Tools
- `analyze_config_changes` - Analyze config file changes
- `analyze_facades` - Check facade usage
- `check_namespace_changes` - Detect namespace changes
- `find_usage_patterns` - Find specific code patterns
- `scan_blade_templates` - Scan Blade templates

### Documentation Tools
- `get_breaking_change_details` - Get specific breaking change
- `get_upgrade_guide` - Get upgrade guide
- `list_deprecated_features` - List deprecated features
- `search_upgrade_docs` - Search documentation

### Migration Tools
- `generate_upgrade_checklist` - Generate upgrade checklist
- `get_code_modification_suggestions` - Get fix suggestions
- `validate_upgrade_step` - Validate upgrade progress

### Package Tools
- `check_package_compatibility` - Check package compatibility
- `suggest_package_updates` - Suggest package updates

## Configuration Options

```php
// config/ascend.php
return [
    'knowledge_base' => [
        'path' => null, // Auto-detects package path
        'cache_enabled' => true,
        'cache_ttl' => 86400, // 24 hours
    ],
    
    'analysis' => [
        'exclude_paths' => [
            'vendor',
            'node_modules',
            'storage',
            'bootstrap/cache',
        ],
        'max_file_size' => 1048576, // 1MB
        'timeout' => 60,
    ],
    
    'tools' => [
        'rate_limit' => 100,
        'enable_all' => true,
        'disabled_tools' => [],
    ],
];
```

## Error Handling

All tools return a standardized response:

```php
[
    'schema_version' => '1.0.0',
    'ok' => true|false,
    'data' => [...], // Success data
    'error' => [     // Error details (if ok=false)
        'message' => '...',
        'code' => '...',
    ],
    'warnings' => [], // Non-fatal warnings
    'timings' => [
        'ms' => 123.45,
    ],
]
```

Example error handling:

```php
$result = $server->callTool('get_upgrade_path', [
    'from_version' => '999',
    'to_version' => '1000',
]);

if (!$result['ok']) {
    logger()->error('Upgrade path failed', [
        'error' => $result['error']['message'],
    ]);
    return;
}

// Process successful result
$upgradePath = $result['data']['upgrade_path'];
```

## Testing

The package includes comprehensive test helpers:

```php
use GoldenPathDigital\LaravelAscend\Server\AscendServer;

test('it analyzes project version', function () {
    $server = AscendServer::createDefault();
    
    $result = $server->callTool('analyze_current_version', [
        'project_root' => __DIR__ . '/fixtures/project',
    ]);
    
    expect($result['ok'])->toBeTrue();
    expect($result['data'])->toHaveKey('current_version');
});
```

## Advanced Usage

### Custom Knowledge Base Path

```php
use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;

$knowledgeBase = KnowledgeBaseService::createDefault('/custom/path');
$server = new AscendServer($knowledgeBase, $toolRegistry);
```

### Register Custom Tools

```php
use GoldenPathDigital\LaravelAscend\Tools\AbstractTool;

class MyCustomTool extends AbstractTool
{
    public function getName(): string
    {
        return 'my_custom_tool';
    }
    
    public function getDescription(): string
    {
        return 'My custom analysis tool';
    }
    
    public function execute(array $payload): array
    {
        // Your logic here
        return $this->success(['result' => 'data']);
    }
}

$server->registerTool(new MyCustomTool());
```

## Troubleshooting

### Server won't start

1. Check port availability: `lsof -i :8765`
2. Verify PHP extensions: `php -m | grep -i socket`
3. Check logs: `tail -f storage/logs/laravel.log`

### Tools return errors

1. Verify project structure: Ensure `composer.json` exists
2. Check file permissions: Tools need read access
3. Increase timeout in config if analyzing large projects

### MCP registration fails

1. Set custom path: `export VSCODE_MCP_CONFIG=/path/to/config`
2. Register manually: `php artisan ascend:register --global`
3. Check IDE MCP settings documentation

## Support

- Issues: https://github.com/goldenpathdigital/laravel-ascend/issues
- Discussions: https://github.com/goldenpathdigital/laravel-ascend/discussions
