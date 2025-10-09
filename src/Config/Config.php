<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Config;

use GoldenPathDigital\LaravelAscend\Exceptions\ConfigException;

final class Config
{
    /** @var array<string, mixed>|null */
    private static ?array $config = null;
    private static ?string $configPath = null;
    private static bool $isLoading = false;

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$config === null) {
            self::load();
        }

        return self::$config ?? [];
    }

    /**
     * Get a configuration value by key using dot notation.
     *
     * @param string $key The configuration key (e.g., 'server.host')
     * @param mixed $default Default value if key not found
     * @return mixed The configuration value or default
     */
    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::all();

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public static function setConfigPath(string $path): void
    {
        self::$configPath = $path;
        self::$config = null; // Reset to force reload
    }

    private static function load(): void
    {
        // Prevent race conditions during concurrent config loading
        if (self::$isLoading) {
            // Wait briefly for config to load
            $retries = 0;
            $maxRetries = 10;
            while ($retries < $maxRetries && self::$config === null) {
                usleep(10000); // 10ms
                $retries++;
            }
            
            // If config was loaded while waiting, return
            if (self::$config !== null) {
                return;
            }
            
            // Otherwise throw exception
            throw ConfigException::loadFailed('Configuration loading timed out (possible race condition)');
        }

        self::$isLoading = true;

        try {
            $paths = self::getConfigPaths();

            foreach ($paths as $path) {
                if (file_exists($path)) {
                    $loaded = require $path;
                    
                    if (!is_array($loaded)) {
                        throw ConfigException::loadFailed(sprintf('Config file "%s" must return an array', $path));
                    }
                    
                    self::$config = $loaded;
                    self::$configPath = $path;
                    return;
                }
            }

            // Fallback to defaults if no config file found
            self::$config = self::getDefaults();
        } finally {
            self::$isLoading = false;
        }
    }

    /**
     * @return array<int, string>
     */
    private static function getConfigPaths(): array
    {
        $paths = [];

        // Custom path if set
        if (self::$configPath !== null) {
            $paths[] = self::$configPath;
        }

        // Current directory
        $cwd = getcwd();
        if ($cwd !== false) {
            $paths[] = $cwd . '/config/ascend.php';
            $paths[] = $cwd . '/.ascend.php';
        }

        // Package directory
        $packageDir = dirname(__DIR__, 2);
        $paths[] = $packageDir . '/config/ascend.php';

        // Environment variable
        $envPath = getenv('ASCEND_CONFIG_PATH');
        if (is_string($envPath) && $envPath !== '') {
            $paths[] = $envPath;
        }

        return $paths;
    }

    /**
     * @return array<string, mixed>
     */
    private static function getDefaults(): array
    {
        return [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8765,
                'protocol' => 'websocket',
            ],
            'knowledge_base' => [
                'path' => null,
                'cache_enabled' => true,
                'cache_ttl' => 86400,
            ],
            'documentation' => [
                'sources' => [
                    'github_org' => 'laravel',
                    'repo_pattern' => 'docs-%s',
                ],
                'auto_update' => false,
                'update_interval' => 604800,
            ],
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
                'max_file_size' => 1048576,
                'max_scan_depth' => 10,
                'timeout' => 60,
            ],
            'breaking_changes' => [
                'severity_levels' => ['critical', 'high', 'medium', 'low'],
                'include_deprecations' => true,
                'group_by' => 'category',
            ],
            'tools' => [
                'rate_limit' => 100,
                'enable_all' => true,
                'disabled_tools' => [],
            ],
            'logging' => [
                'enabled' => true,
                'level' => 'info',
                'channel' => 'stack',
            ],
            'security' => [
                'require_auth' => false,
                'auth_token' => null,
                'allowed_origins' => ['*'],
                'read_only' => true,
            ],
            'performance' => [
                'concurrent_requests' => 10,
                'memory_limit' => '256M',
                'enable_caching' => true,
            ],
            'upgrade' => [
                'allow_version_skipping' => false,
                'recommend_incremental' => true,
                'php_version_check' => true,
                'package_compatibility_check' => true,
            ],
        ];
    }

    /**
     * Reset the configuration (useful for testing)
     */
    public static function reset(): void
    {
        self::$config = null;
        self::$configPath = null;
        self::$isLoading = false;
    }
}
