# Laravel Ascend Architecture

## Overview

Laravel Ascend is an MCP (Model Context Protocol) server that provides AI coding agents with structured access to Laravel upgrade documentation and code analysis tools. The architecture follows a layered design with clear separation of concerns.

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         MCP Client Layer                          │
│              (Cline, Claude Desktop, Custom Clients)              │
└───────────────────────────┬───────────────────────────────────────┘
                            │ STDIO Protocol
                            │ (JSON-RPC Messages)
┌───────────────────────────┴───────────────────────────────────────┐
│                      MCP Server Layer                              │
│  ┌──────────────────────────────────────────────────────────────┐ │
│  │              McpStdioServer (Entry Point)                     │ │
│  │  - Handles STDIN/STDOUT communication                         │ │
│  │  - JSON-RPC message parsing                                   │ │
│  │  - Protocol version negotiation                               │ │
│  └────────────────────────┬─────────────────────────────────────┘ │
│                           │                                         │
│  ┌────────────────────────┴─────────────────────────────────────┐ │
│  │           McpRequestHandler (Request Router)                  │ │
│  │  - initialize, tools/list, tools/call                         │ │
│  │  - resources/list, resources/read                             │ │
│  │  - prompts/list, prompts/get                                  │ │
│  └────────────────────────┬─────────────────────────────────────┘ │
│                           │                                         │
│  ┌────────────────────────┴─────────────────────────────────────┐ │
│  │              AscendServer (Core Orchestrator)                 │ │
│  │  - Server information & capabilities                          │ │
│  │  - Tool registry management                                   │ │
│  │  - Resource & prompt management                               │ │
│  │  - Knowledge base integration                                 │ │
│  └────────────────────────┬─────────────────────────────────────┘ │
└───────────────────────────┼───────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
┌───────▼────────┐  ┌──────▼────────┐  ┌──────▼────────┐
│  Tool Registry │  │   Knowledge   │  │   Resources   │
│                │  │     Base      │  │  & Prompts    │
│ - 15+ Tools    │  │   Service     │  │               │
│ - Auto-        │  │               │  │ - Index       │
│   discovery    │  │ - Loader      │  │ - Patterns    │
│ - Validation   │  │ - Parser      │  │ - Paths       │
└────────┬───────┘  │ - Search      │  │ - Changes     │
         │          └───────┬───────┘  └───────────────┘
         │                  │
┌────────▼──────────────────▼────────────────────────────┐
│              Implementation Layer                       │
│                                                         │
│  ┌────────────────┐  ┌──────────────┐  ┌────────────┐ │
│  │   Analysis     │  │     Code     │  │  Package   │ │
│  │     Tools      │  │   Analysis   │  │   Tools    │ │
│  │                │  │    Tools     │  │            │ │
│  │ - Version      │  │              │  │ - Compat   │ │
│  │ - Dependencies │  │ - Facades    │  │ - Updates  │ │
│  │ - PHP Check    │  │ - Namespaces │  │            │ │
│  │ - Scan         │  │ - Configs    │  │            │ │
│  │                │  │ - Blade      │  │            │ │
│  └────────────────┘  └──────────────┘  └────────────┘ │
│                                                         │
│  ┌──────────────────────┐  ┌──────────────────────┐   │
│  │  Documentation Tools │  │   Migration Tools    │   │
│  │                      │  │                      │   │
│  │ - Upgrade Guide      │  │ - Checklist Gen      │   │
│  │ - Search Docs        │  │ - Suggestions        │   │
│  │ - Breaking Changes   │  │ - Validation         │   │
│  │ - Deprecated List    │  │                      │   │
│  └──────────────────────┘  └──────────────────────┘   │
└─────────────────────────────────────────────────────────┘
                            │
┌───────────────────────────▼───────────────────────────┐
│              Support Services                          │
│                                                        │
│  ┌────────────┐  ┌───────────┐  ┌──────────────┐    │
│  │   Cache    │  │   Config  │  │  Exceptions  │    │
│  │  Manager   │  │           │  │              │    │
│  │            │  │ - Static  │  │ - Cache      │    │
│  │ - LRU      │  │ - Loader  │  │ - Config     │    │
│  │ - TTL      │  │           │  │ - Tool       │    │
│  └────────────┘  └───────────┘  └──────────────┘    │
└────────────────────────────────────────────────────────┘
```

## Core Components

### 1. MCP Server Layer

#### McpStdioServer
- **Purpose**: Entry point for STDIO-based MCP protocol communication
- **Responsibilities**:
  - Read JSON-RPC requests from STDIN
  - Write JSON-RPC responses to STDOUT
  - Handle protocol errors and validation
  - Manage server lifecycle

#### McpRequestHandler
- **Purpose**: Route MCP protocol requests to appropriate handlers
- **Key Methods**:
  - `initialize`: Server capabilities negotiation
  - `tools/list`: List available tools
  - `tools/call`: Execute tool with parameters
  - `resources/list`: List available resources
  - `resources/read`: Read resource content
  - `prompts/list`: List available prompts
  - `prompts/get`: Get prompt template

#### McpRegistration
- **Purpose**: Register server with IDE/client configuration files
- **Supported Targets**:
  - Project-level: `.vscode/mcp.json`, `.cursor/mcp.json`, `.junie/mcp/mcp.json`
  - User-level: Various IDE-specific paths
  - Format support: JSON and TOML (Codex)

### 2. Core Server (AscendServer)

The `AscendServer` class is the central orchestrator that:
- Manages tool registry and execution
- Provides knowledge base access
- Handles resource and prompt descriptors
- Exposes server metadata and capabilities

**Key Methods**:
```php
// Server Information
getServerName(): string
getServerVersion(): string
getSupportedProtocolVersions(): array

// Tool Management
callTool(string $toolName, array $payload): array
listToolNames(): array
describeTools(): array

// Knowledge Base
searchKnowledgeBase(string $query, int $limit): array
getBreakingChangeDocument(string $slug): array
getPattern(string $patternId): array
getUpgradePath(string $identifier): array
```

### 3. Tool System

#### Tool Registry
- **Auto-discovery**: Scans `src/Tools` directory recursively
- **Registration**: Validates and registers tools implementing `ToolInterface`
- **Execution**: Routes tool calls with parameter validation

#### Tool Interface
All tools must implement:
```php
interface ToolInterface {
    public function getName(): string;
    public function getDescription(): string;
    public function execute(array $payload): array;
    public function getInputSchema(): array;
    public function getAnnotations(): array;
}
```

#### Tool Categories

**Analysis Tools** (`src/Tools/Analysis/`)
- Analyze Laravel version
- Check PHP compatibility
- Scan dependencies
- Detect breaking changes
- Generate upgrade paths

**Code Analysis Tools** (`src/Tools/Code/`)
- Analyze configuration changes
- Check facade usage
- Detect namespace changes
- Find usage patterns
- Scan Blade templates

**Documentation Tools** (`src/Tools/Documentation/`)
- Search upgrade documentation
- Get breaking change details
- List deprecated features
- Retrieve upgrade guides

**Migration Tools** (`src/Tools/Migration/`)
- Generate upgrade checklists
- Provide code modification suggestions
- Validate upgrade steps

**Package Tools** (`src/Tools/Package/`)
- Check package compatibility
- Suggest package updates

### 4. Knowledge Base Service

#### Structure
```
resources/knowledge-base/
├── index.json                    # Metadata and version info
├── breaking-changes/
│   ├── laravel-7.json
│   ├── laravel-8.json
│   └── ... (laravel-12.json)
├── patterns/
│   ├── factory-class-rewrite.json
│   ├── model-dates-to-cast.json
│   └── ... (8 patterns total)
└── upgrade-paths/
    └── upgrade-paths.json        # Version transition roadmaps
```

#### DocumentationLoader
- Loads JSON files from knowledge base
- Validates required fields
- Builds in-memory indexes
- Caches parsed content

#### SearchIndex
- Full-text search across documentation
- Relevance scoring
- Version-specific queries
- Pattern matching

#### DocumentationParser
- Markdown processing
- Code example extraction
- Link resolution
- Metadata extraction

### 5. Resource System

Resources provide static content access:
- **Breaking Changes Index**: All breaking changes by version
- **Patterns Index**: Migration patterns catalog
- **Upgrade Paths**: Version transition roadmaps
- **Knowledge Base Summary**: Overview of available content

Resources are auto-discovered from `src/Resources/` implementing `ResourceInterface`.

### 6. Prompt System

Prompts provide templated guidance:
- **Breaking Change Pattern**: Analyze and fix breaking changes
- **Package Upgrade**: Package compatibility and updates
- **Upgrade Foundation**: Foundation for upgrade planning

Prompts are auto-discovered from `src/Prompts/` implementing `PromptInterface`.

## Data Flow

### Tool Execution Flow

```
1. Client sends tools/call request
   ↓
2. McpStdioServer receives from STDIN
   ↓
3. McpRequestHandler parses JSON-RPC
   ↓
4. AscendServer.callTool() invoked
   ↓
5. ToolRegistry validates and routes
   ↓
6. Specific tool executes
   ↓
7. Tool returns standardized response
   ↓
8. Response formatted as JSON-RPC
   ↓
9. McpStdioServer writes to STDOUT
   ↓
10. Client receives result
```

### Knowledge Base Query Flow

```
1. Tool needs documentation
   ↓
2. Calls KnowledgeBaseService
   ↓
3. SearchIndex queries in-memory data
   ↓
4. Results scored and ranked
   ↓
5. DocumentationParser formats output
   ↓
6. Cached for subsequent queries
   ↓
7. Returns to tool
```

## Response Format

All tool responses follow a standard schema:

```json
{
  "schema_version": "1.0.0",
  "ok": true,
  "data": {
    // Tool-specific response data
  },
  "warnings": [],
  "timings": {
    "ms": 123.45
  }
}
```

Error responses:

```json
{
  "schema_version": "1.0.0",
  "ok": false,
  "error": {
    "message": "Error description",
    "code": "ERROR_CODE"
  },
  "warnings": [],
  "timings": {
    "ms": 10.5
  }
}
```

## Laravel Integration

### Service Provider (AscendServiceProvider)

Automatically registers:
- Configuration files
- Artisan commands (`ascend:mcp`, `ascend:register`)
- Facade binding
- Tool auto-discovery

### Facade (Ascend)

Provides convenient static access:
```php
use GoldenPathDigital\LaravelAscend\Facades\Ascend;

Ascend::callTool('analyze_current_version', [...]);
Ascend::searchKnowledgeBase('middleware');
```

### Artisan Commands

**`ascend:mcp`**
- Starts STDIO MCP server
- Options: `--kb-path` for custom knowledge base

**`ascend:register`**
- Registers server with IDE configs
- Options: `--global` for user-level only, `--all` for all targets

## Configuration

Configuration in `config/ascend.php`:

```php
return [
    'knowledge_base' => [
        'path' => null,              // Auto-detects
        'cache_enabled' => true,
        'cache_ttl' => 86400,
    ],
    'analysis' => [
        'exclude_paths' => [...],
        'max_file_size' => 1048576,
        'timeout' => 60,
    ],
    'tools' => [
        'rate_limit' => 100,
        'enable_all' => true,
        'disabled_tools' => [],
    ],
];
```

## Security Considerations

1. **Read-Only Operations**: All tools are read-only; no file modifications
2. **Path Validation**: All file operations validate paths are within project
3. **Input Validation**: All tool inputs validated against schemas
4. **Resource Limits**: File size limits, scan depth limits
5. **No Network Calls**: Except for optional documentation caching command
6. **Error Sanitization**: Error messages don't leak sensitive information

## Performance Optimizations

1. **In-Memory Caching**: Knowledge base cached after first load
2. **Lazy Loading**: Tools registered but not instantiated until needed
3. **Search Indexing**: Pre-computed indexes for fast queries
4. **LRU Cache**: Least-recently-used eviction for memory management
5. **Streaming Responses**: Large responses can be streamed
6. **Auto-discovery Limits**: Safety limits on file scanning

## Extension Points

### Custom Tools
Implement `ToolInterface` and place in `src/Tools/` subdirectory:
```php
class MyCustomTool extends AbstractTool {
    public function getName(): string { return 'my_tool'; }
    public function execute(array $payload): array { ... }
}
```

### Custom Knowledge Base
Provide custom path via `--kb-path` or config:
```php
$kb = KnowledgeBaseService::createDefault('/custom/path');
```

### Custom Resources
Implement `ResourceInterface` in `src/Resources/`:
```php
class MyResource implements ResourceInterface {
    public function uri(): string { ... }
    public function name(): string { ... }
    public function toArray(): array { ... }
}
```

### Custom Prompts
Implement `PromptInterface` in `src/Prompts/`:
```php
class MyPrompt implements PromptInterface {
    public function name(): string { ... }
    public function toArray(): array { ... }
}
```

## Testing Strategy

- **Unit Tests**: Individual components isolated
- **Integration Tests**: Tool execution end-to-end
- **Contract Tests**: Response schema validation
- **Performance Tests**: Benchmark critical paths
- **Mutation Testing**: Code coverage quality
- **Static Analysis**: PHPStan level 8

## Protocol Versions

Ascend supports multiple MCP protocol versions:
- **2025-06-18**: Current primary version
- **2024-11-05**: Legacy support
- **2024-10-07**: Legacy support

Version negotiation happens during `initialize` request.

## Future Architecture Considerations

- **Streaming Responses**: For large result sets
- **Parallel Tool Execution**: Execute independent tools concurrently
- **Persistent Sessions**: Maintain state across requests
- **Plugin System**: Dynamic tool loading without code changes
- **Event System**: Hook into tool lifecycle
- **Metrics Collection**: Performance and usage analytics
- **WebSocket Support**: Alternative transport (requires Ratchet dependency)
