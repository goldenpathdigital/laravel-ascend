# Laravel Ascend - API Reference

## Server API

### AscendServer

Main server class providing access to tools, resources, and knowledge base.

#### Constructor

```php
public function __construct(
    KnowledgeBaseService $knowledgeBase,
    ToolRegistry $toolRegistry,
    array $resourceDescriptors = [],
    array $promptDescriptors = []
)
```

#### Static Factory

```php
public static function createDefault(?string $knowledgeBasePath = null): self
```

Creates a server instance with default configuration and auto-registered tools.

**Parameters:**
- `$knowledgeBasePath` (string|null): Custom path to knowledge base, defaults to package resources

**Returns:** Configured `AscendServer` instance

---

### Server Information

#### getServerName()

```php
public function getServerName(): string
```

Returns: `'Laravel Ascend'`

#### getServerVersion()

```php
public function getServerVersion(): string
```

Returns: Current package version (e.g., `'0.1.0'`)

#### getSupportedProtocolVersions()

```php
public function getSupportedProtocolVersions(): array<int, string>
```

Returns: Array of supported MCP protocol versions

---

### Knowledge Base Methods

#### searchKnowledgeBase()

```php
public function searchKnowledgeBase(string $query, int $limit = 10): array<int, array<string, mixed>>
```

Search the knowledge base for relevant documentation.

**Parameters:**
- `$query` (string): Search query
- `$limit` (int): Maximum results to return (default: 10)

**Returns:** Array of search results with relevance scores

**Example:**
```php
$results = $server->searchKnowledgeBase('middleware changes', 5);
```

#### getBreakingChangeDocument()

```php
public function getBreakingChangeDocument(string $slug): array<string, mixed>
```

Get full breaking change document by slug.

**Parameters:**
- `$slug` (string): Document slug (e.g., 'laravel-11')

**Returns:** Complete breaking change document

**Throws:** `DocumentationException` if slug not found

#### getBreakingChangeEntry()

```php
public function getBreakingChangeEntry(string $slug, string $changeId): array<string, mixed>
```

Get specific breaking change entry.

**Parameters:**
- `$slug` (string): Document slug
- `$changeId` (string): Change identifier

**Returns:** Breaking change details

**Example:**
```php
$change = $server->getBreakingChangeEntry('laravel-11', 'middleware-signature');
```

#### getPattern()

```php
public function getPattern(string $patternId): array<string, mixed>
```

Get upgrade pattern by identifier.

**Parameters:**
- `$patternId` (string): Pattern identifier

**Returns:** Pattern with detection rules and fixes

#### getUpgradePath()

```php
public function getUpgradePath(string $identifier): array<string, mixed>
```

Get upgrade path by identifier.

**Parameters:**
- `$identifier` (string): Path identifier (e.g., '10-to-11')

**Returns:** Upgrade path details

---

### Tool Methods

#### callTool()

```php
public function callTool(string $toolName, array $payload = []): array<string, mixed>
```

Execute a registered tool.

**Parameters:**
- `$toolName` (string): Name of tool to execute
- `$payload` (array): Tool parameters

**Returns:** Standardized tool response

**Response Format:**
```php
[
    'schema_version' => '1.0.0',
    'ok' => true|false,
    'data' => [...],        // Success data
    'error' => [...],       // Error details (if ok=false)
    'warnings' => [],       // Non-fatal warnings
    'timings' => ['ms' => 123.45],
]
```

#### listToolNames()

```php
public function listToolNames(): array<int, string>
```

Returns: Array of registered tool names

#### describeTools()

```php
public function describeTools(): array<int, array<string, mixed>>
```

Returns: Array of tool descriptors with schemas

---

## Exception Classes

### CacheException

Exception thrown for cache-related errors.

#### Static Methods

```php
public static function invalidKey(string $key): self
```

Creates exception for invalid cache key format.

```php
public static function valueTooLarge(string $key, int $size, int $maxSize): self
```

Creates exception when cached value exceeds size limit.

---

### ConfigException

Exception thrown for configuration errors.

#### Static Methods

```php
public static function fileNotFound(string $path): self
```

```php
public static function invalidKey(string $key): self
```

```php
public static function loadFailed(string $reason): self
```

---

### ToolException

Exception thrown for tool-related errors.

#### Static Methods

```php
public static function notRegistered(string $toolName): self
```

```php
public static function invalidInput(string $message): self
```

```php
public static function executionFailed(string $toolName, string $reason): self
```

---

## Tool Interface

### ToolInterface

Interface that all tools must implement.

```php
interface ToolInterface
{
    public function getName(): string;
    public function getSchemaVersion(): string;
    public function getDescription(): string;
    public function execute(array $payload): array;
    public function getInputSchema(): array;
    public function getAnnotations(): array;
}
```

### AbstractTool

Base class providing common tool functionality.

#### Protected Methods

```php
protected function success(
    array $data,
    array $warnings = [],
    float $startedAt = null
): array
```

Returns standardized success response.

```php
protected function error(
    string $message,
    array $warnings = [],
    float $startedAt = null,
    ?string $code = null
): array
```

Returns standardized error response.

---

## Cache Manager

### CacheManager

In-memory cache with LRU eviction.

#### Constructor

```php
public function __construct(
    int $defaultTtl = 3600,
    int $maxCacheSize = 100,
    int $maxValueSize = 1048576
)
```

**Parameters:**
- `$defaultTtl` (int): Default time-to-live in seconds
- `$maxCacheSize` (int): Maximum number of cache entries
- `$maxValueSize` (int): Maximum serialized value size in bytes

#### Methods

```php
public function set(string $key, mixed $value, ?int $ttl = null): void
```

Store value in cache.

**Throws:** `CacheException` for invalid keys or oversized values

```php
public function get(string $key, mixed $default = null): mixed
```

Retrieve value from cache, returns default if not found or expired.

```php
public function has(string $key): bool
```

Check if key exists in cache.

```php
public function forget(string $key): void
```

Remove value from cache.

```php
public function clear(): void
```

Clear all cached values.

```php
public function remember(string $key, callable $callback, ?int $ttl = null): mixed
```

Get value from cache or execute callback and cache result.

```php
public function stats(): array<string, mixed>
```

Get cache statistics (size, memory usage).

---

## Configuration

### Config

Static configuration manager.

#### Methods

```php
public static function all(): array<string, mixed>
```

Get all configuration values.

```php
public static function get(string $key, mixed $default = null): mixed
```

Get configuration value by dot notation key.

**Example:**
```php
$host = Config::get('server.host', '127.0.0.1');
```

```php
public static function setConfigPath(string $path): void
```

Set custom configuration file path.

```php
public static function reset(): void
```

Reset configuration (useful for testing).

---

## Facade

### Ascend

Laravel facade for convenient access.

```php
use GoldenPathDigital\LaravelAscend\Facades\Ascend;

// All AscendServer methods available
$version = Ascend::callTool('analyze_current_version', [
    'project_root' => base_path(),
]);

$results = Ascend::searchKnowledgeBase('middleware');
```

To use the facade, ensure AscendServer is bound in your service provider (automatically done by package).

---

## Tool Response Schema

All tools return responses conforming to this schema:

```php
[
    'schema_version' => '1.0.0',  // Response schema version
    'ok' => true,                   // Success indicator
    'data' => [                     // Tool-specific data
        // ... tool results
    ],
    'warnings' => [],               // Non-fatal warnings
    'timings' => [
        'ms' => 123.45,            // Execution time in milliseconds
    ],
]
```

Error response:

```php
[
    'schema_version' => '1.0.0',
    'ok' => false,
    'error' => [
        'message' => 'Error description',
        'code' => 'ERROR_CODE',    // Optional error code
    ],
    'warnings' => [],
    'timings' => ['ms' => 10.5],
]
```

---

## Testing Helpers

### TestCase

Extend this class for package tests:

```php
use GoldenPathDigital\LaravelAscend\Tests\TestCase;

class MyTest extends TestCase
{
    // Test methods
}
```

### Available Assertions

```php
expect($result['ok'])->toBeTrue();
expect($result)->toHaveKey('data');
expect($result['data'])->toHaveKey('current_version');
```

---

## Event Hooks

Currently, the package does not emit Laravel events. If you need hooks:

1. Extend `AscendServer` and override methods
2. Dispatch custom events in your implementation
3. Create a custom service provider to bind your extended class

Example:

```php
class CustomAscendServer extends AscendServer
{
    public function callTool(string $toolName, array $payload = []): array
    {
        event(new ToolExecuting($toolName, $payload));
        
        $result = parent::callTool($toolName, $payload);
        
        event(new ToolExecuted($toolName, $result));
        
        return $result;
    }
}
```

---

## Type Definitions

### Common Types

```php
// Tool payload
array<string, mixed>

// Tool response
array{
    schema_version: string,
    ok: bool,
    data?: array<string, mixed>,
    error?: array{message: string, code?: string},
    warnings: array<int, string>,
    timings: array{ms: float}
}

// Search results
array<int, array{
    id: string,
    title: string,
    content: string,
    score: float,
    metadata: array<string, mixed>
}>
```

---

## Version Compatibility

- **PHP**: 7.4+ / 8.x (supports PHP 7.4, 8.0, 8.1, 8.2, 8.3)
- **Laravel**: 6.x - 11.x
- **MCP Protocol**: 2025-06-18 (primary), 2024-11-05 (legacy), 2024-10-07 (legacy)

---

## Extension Points

### Custom Tools

```php
use GoldenPathDigital\LaravelAscend\Tools\AbstractTool;

class MyTool extends AbstractTool
{
    public function getName(): string
    {
        return 'my_tool';
    }
    
    public function getDescription(): string
    {
        return 'My custom tool';
    }
    
    public function execute(array $payload): array
    {
        try {
            // Your logic
            return $this->success(['result' => 'data']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'param1' => ['type' => 'string', 'description' => '...'],
            ],
            'required' => ['param1'],
        ];
    }
}
```

### Custom Knowledge Base

```php
use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;

$kb = new KnowledgeBaseService($loader, $searchIndex);
$server = new AscendServer($kb, $toolRegistry);
```

---

For more examples, see [USAGE.md](USAGE.md).
