<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Knowledge Base Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the Laravel upgrade knowledge base including caching,
    | documentation sources, and update frequency.
    |
    */
    'knowledge_base' => [
        'path' => env('ASCEND_KNOWLEDGE_BASE_PATH', null),
        'cache_enabled' => env('ASCEND_CACHE_ENABLED', true),
        'cache_ttl' => (int) env('ASCEND_CACHE_TTL', 86400), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Sources
    |--------------------------------------------------------------------------
    |
    | Configuration for fetching and updating Laravel documentation.
    | By default, uses Laravel's official GitHub documentation.
    |
    */
    'documentation' => [
        'sources' => [
            'github_org' => env('ASCEND_DOCS_GITHUB_ORG', 'laravel'),
            'repo_pattern' => env('ASCEND_DOCS_REPO_PATTERN', 'docs-%s'), // %s = version
        ],
        'auto_update' => env('ASCEND_DOCS_AUTO_UPDATE', false),
        'update_interval' => (int) env('ASCEND_DOCS_UPDATE_INTERVAL', 604800), // 7 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Code Analysis Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for analyzing Laravel projects, including paths to exclude
    | and file size limits for processing.
    |
    */
    'analysis' => [
        'exclude_paths' => [
            'vendor',
            'node_modules',
            'storage',
            'bootstrap/cache',
            '.git',
            'public/build',
            'public/hot',
        ],
        'max_file_size' => (int) env('ASCEND_MAX_FILE_SIZE', 1048576), // 1MB
        'max_scan_depth' => (int) env('ASCEND_MAX_SCAN_DEPTH', 10),
        'timeout' => (int) env('ASCEND_ANALYSIS_TIMEOUT', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Breaking Change Detection
    |--------------------------------------------------------------------------
    |
    | Configure how breaking changes are detected and reported during
    | upgrade analysis.
    |
    */
    'breaking_changes' => [
        'severity_levels' => ['critical', 'high', 'medium', 'low'],
        'include_deprecations' => env('ASCEND_INCLUDE_DEPRECATIONS', true),
        'group_by' => env('ASCEND_GROUP_BREAKING_CHANGES', 'category'), // category, file, severity
    ],

    /*
    |--------------------------------------------------------------------------
    | Tool Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for individual MCP tools, including rate limits and
    | feature flags.
    |
    */
    'tools' => [
        'rate_limit' => (int) env('ASCEND_TOOL_RATE_LIMIT', 100), // requests per minute
        'enable_all' => env('ASCEND_ENABLE_ALL_TOOLS', true),
        'disabled_tools' => explode(',', env('ASCEND_DISABLED_TOOLS', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for the Ascend MCP server and tools.
    |
    */
    'logging' => [
        'enabled' => env('ASCEND_LOGGING_ENABLED', true),
        'level' => env('ASCEND_LOG_LEVEL', 'info'), // debug, info, warning, error
        'channel' => env('ASCEND_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for the MCP server including authentication
    | and authorization options.
    |
    */
    'security' => [
        'require_auth' => env('ASCEND_REQUIRE_AUTH', false),
        'auth_token' => env('ASCEND_AUTH_TOKEN', null),
        'allowed_origins' => explode(',', env('ASCEND_ALLOWED_ORIGINS', '*')),
        'read_only' => env('ASCEND_READ_ONLY', true), // Never allow file modifications
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for optimizing performance and resource usage.
    |
    */
    'performance' => [
        'concurrent_requests' => (int) env('ASCEND_CONCURRENT_REQUESTS', 10),
        'memory_limit' => env('ASCEND_MEMORY_LIMIT', '256M'),
        'enable_caching' => env('ASCEND_ENABLE_CACHING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Upgrade Path Configuration
    |--------------------------------------------------------------------------
    |
    | Configure upgrade path recommendations and validation.
    |
    */
    'upgrade' => [
        'allow_version_skipping' => env('ASCEND_ALLOW_VERSION_SKIPPING', false),
        'recommend_incremental' => env('ASCEND_RECOMMEND_INCREMENTAL', true),
        'php_version_check' => env('ASCEND_PHP_VERSION_CHECK', true),
        'package_compatibility_check' => env('ASCEND_PACKAGE_COMPATIBILITY_CHECK', true),
    ],
];
