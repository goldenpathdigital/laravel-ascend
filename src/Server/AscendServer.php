<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Server;

use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use GoldenPathDigital\LaravelAscend\Tools\ToolInterface;
use GoldenPathDigital\LaravelAscend\Tools\ToolRegistry;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionNamedType;

final class AscendServer
{
    /** @var KnowledgeBaseService */
    private $knowledgeBase;

    /** @var ToolRegistry */
    private $toolRegistry;

    /** @var array<int, array<string, mixed>> */
    private $resourceDescriptors;

    /** @var array<int, array<string, mixed>> */
    private $promptDescriptors;

    /**
     * @param array<int, array<string, mixed>> $resourceDescriptors
     * @param array<int, array<string, mixed>> $promptDescriptors
     */
    public function __construct(
        KnowledgeBaseService $knowledgeBase,
        ToolRegistry $toolRegistry,
        array $resourceDescriptors = [],
        array $promptDescriptors = []
    ) {
        $this->knowledgeBase = $knowledgeBase;
        $this->toolRegistry = $toolRegistry;
        $this->resourceDescriptors = $resourceDescriptors;
        $this->promptDescriptors = $promptDescriptors;
    }

    public static function createDefault(?string $knowledgeBasePath = null): self
    {
        $knowledgeBase = KnowledgeBaseService::createDefault($knowledgeBasePath);
        $registry = new ToolRegistry();

        self::registerDefaultTools($registry, $knowledgeBase);

        $resourceDescriptors = self::discoverResourceDescriptors();
        $promptDescriptors = self::discoverPromptDescriptors();

        return new self($knowledgeBase, $registry, $resourceDescriptors, $promptDescriptors);
    }

    public function getServerName(): string
    {
        return 'Laravel Ascend';
    }

    public function getServerVersion(): string
    {
        static $version = null;

        if ($version === null) {
            $composerPath = dirname(__DIR__, 2) . '/composer.json';

            if (file_exists($composerPath)) {
                $contents = file_get_contents($composerPath);
                if ($contents !== false) {
                    $data = json_decode($contents, true);
                    if (isset($data['version'])) {
                        $version = $data['version'];
                    }
                }
            }

            // Fallback if version not found
            if ($version === null) {
                $version = '0.1.0-dev';
            }
        }

        return $version;
    }

    public function getInstructions(): string
    {
        return implode(PHP_EOL, [
            'Ascend exposes structured Laravel upgrade guidance, analyzers, and migration utilities.',
            '',
            'Before starting an upgrade, establish a baseline (no code or dependency changes until this is complete):',
            '- Run the full test suite and record the results.',
            '- Capture key performance and error-rate metrics.',
            '- Back up the database and storage assets.',
            '- Tag or note the current git commit, ensuring a clean working tree.',
            '- Document the active runtime environment (PHP version, extensions, queues, schedulers).',
            '',
            'Tool usage discipline:',
            '- Call `describeTools` to inspect schemas and required parameters before invoking anything.',
            '- Only invoke tools when the answer is not already available in the current context.',
            '- Avoid re-running a tool unless new inputs would change the result; reference prior outputs instead.',
            '- Capture summaries/identifiers from each tool call in your working notes and reuse them instead of re-fetching.',
            '- Summarise tool responses concisely (≤5 bullet points) and store key identifiers for reuse.',
            '- Prefer purpose-built analyzers over manual grepping; tools give structured, reusable data.',
            '- Trim or summarise large outputs before replying to keep the conversation context lean.',
            '- Build or update your plan before launching tool calls so heavier scans happen precisely when their results will be applied.',
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function getSupportedProtocolVersions(): array
    {
        return [
            '2025-06-18',  // Current MCP specification version
            '2024-11-05',  // Legacy version for backward compatibility
            '2024-10-07',  // Additional legacy support
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCapabilities(): array
    {
        return [
            'tools' => [
                'listChanged' => false,
            ],
            'resources' => [
                'listChanged' => false,
            ],
            'prompts' => [
                'listChanged' => false,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getKnowledgeBaseInfo(): array
    {
        return $this->knowledgeBase->getSummary();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchKnowledgeBase(string $query, int $limit = 10): array
    {
        return $this->knowledgeBase->search($query, $limit);
    }

    /**
     * @return array<string, mixed>
     */
    public function getBreakingChangeDocument(string $slug): array
    {
        return $this->knowledgeBase->getBreakingChangeDocument($slug);
    }

    /**
     * @return array<string, mixed>
     */
    public function getBreakingChangeEntry(string $slug, string $changeId): array
    {
        return $this->knowledgeBase->getBreakingChangeEntry($slug, $changeId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPattern(string $patternId): array
    {
        return $this->knowledgeBase->getPattern($patternId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUpgradePath(string $identifier): array
    {
        return $this->knowledgeBase->getUpgradePath($identifier);
    }

    /**
     * @return array<int, string>
     */
    public function listUpgradePathIdentifiers(): array
    {
        return $this->knowledgeBase->listUpgradePathIdentifiers();
    }

    /**
     * @return array<int, string>
     */
    public function listPatternIdentifiers(): array
    {
        return $this->knowledgeBase->listPatternIdentifiers();
    }

    /**
     * @return array<int, string>
     */
    public function listBreakingChangeSlugs(): array
    {
        return $this->knowledgeBase->listBreakingChangeSlugs();
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function callTool(string $toolName, array $payload = []): array
    {
        return $this->toolRegistry->invoke($toolName, $payload);
    }

    /**
     * @return array<int, string>
     */
    public function listToolNames(): array
    {
        return $this->toolRegistry->list();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function describeTools(): array
    {
        $registry = $this->toolRegistry;

        return array_map(
            function (string $toolName) use ($registry): array {
                $tool = $registry->get($toolName);

                return [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'inputSchema' => $tool->getInputSchema(),
                    'annotations' => $tool->getAnnotations() ?: (object) []
                ];
            },
            $registry->list()
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function describeResources(): array
    {
        return $this->resourceDescriptors;
    }

    /**
     * Read a specific resource by URI
     *
     * @return array<string, mixed>|null
     */
    public function readResource(string $uri): ?array
    {
        foreach ($this->resourceDescriptors as $descriptor) {
            if (($descriptor['uri'] ?? null) === $uri) {
                return $descriptor;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function describePrompts(): array
    {
        return $this->promptDescriptors;
    }

    public function registerTool(ToolInterface $tool): void
    {
        $this->toolRegistry->register($tool);
    }

    public function getKnowledgeBaseService(): KnowledgeBaseService
    {
        return $this->knowledgeBase;
    }

    public function getToolRegistry(): ToolRegistry
    {
        return $this->toolRegistry;
    }

    private static function registerDefaultTools(ToolRegistry $registry, KnowledgeBaseService $knowledgeBase): void
    {
        // Documentation tools
        foreach (self::discoverToolClasses() as $toolClass) {
            $instance = self::instantiateTool($toolClass, $knowledgeBase);

            if ($instance === null) {
                continue;
            }

            $registry->register($instance);
        }
    }

    /**
     * @return array<int, string>
     */
    private static function discoverToolClasses(): array
    {
        $baseDir = dirname(__DIR__) . '/Tools';
        $baseNamespace = 'GoldenPathDigital\\LaravelAscend\\Tools\\';

        if (!is_dir($baseDir)) {
            return [];
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            $iterator->setMaxDepth(5); // Limit recursion depth
        } catch (\Exception $e) {
            return [];
        }

        $classes = [];
        $fileCount = 0;
        $maxFiles = 100; // Safety limit

        foreach ($iterator as $file) {
            if (++$fileCount > $maxFiles) {
                break; // Safety break to prevent excessive scanning
            }

            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($baseDir) + 1);
            $relative = substr($relative, 0, -4); // strip .php
            $class = $baseNamespace . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

            if (!class_exists($class)) {
                continue;
            }

            if (!is_subclass_of($class, ToolInterface::class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            $classes[] = $class;
        }

        sort($classes);

        return $classes;
    }

    private static function instantiateTool(string $class, KnowledgeBaseService $knowledgeBase): ?ToolInterface
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0) {
            $instance = $reflection->newInstance();

            return $instance instanceof ToolInterface ? $instance : null;
        }

        $args = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $type->getName() === KnowledgeBaseService::class) {
                $args[] = $knowledgeBase;

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();

                continue;
            }

            return null;
        }

        $instance = $reflection->newInstanceArgs($args);

        return $instance instanceof ToolInterface ? $instance : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function discoverResourceDescriptors(): array
    {
        $baseDir = dirname(__DIR__) . '/Resources';
        $baseNamespace = 'GoldenPathDigital\\LaravelAscend\\Resources\\';

        if (!is_dir($baseDir)) {
            return [];
        }

        $knowledgeBase = KnowledgeBaseService::createDefault();
        return self::discoverDescriptors($baseDir, $baseNamespace, \GoldenPathDigital\LaravelAscend\Server\Mcp\Contracts\ResourceInterface::class, $knowledgeBase);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function discoverPromptDescriptors(): array
    {
        $baseDir = dirname(__DIR__) . '/Prompts';
        $baseNamespace = 'GoldenPathDigital\\LaravelAscend\\Prompts\\';

        if (!is_dir($baseDir)) {
            return [];
        }

        return self::discoverDescriptors($baseDir, $baseNamespace, \GoldenPathDigital\LaravelAscend\Server\Mcp\Contracts\PromptInterface::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function discoverDescriptors(string $baseDir, string $baseNamespace, string $contract, ?KnowledgeBaseService $knowledgeBase = null): array
    {
        if (!is_dir($baseDir)) {
            return [];
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            $iterator->setMaxDepth(5); // Limit recursion depth
        } catch (\Exception $e) {
            return [];
        }

        $results = [];
        $fileCount = 0;
        $maxFiles = 50; // Safety limit for resources/prompts

        foreach ($iterator as $file) {
            if (++$fileCount > $maxFiles) {
                break; // Safety break to prevent excessive scanning
            }

            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($baseDir) + 1, -4);
            $class = $baseNamespace . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

            if (!class_exists($class) || !is_subclass_of($class, $contract)) {
                continue;
            }

            /** @var class-string $class */
            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            $instance = self::instantiateDescriptor($reflection, $knowledgeBase);

            if ($instance === null) {
                continue;
            }

            if (!method_exists($instance, 'toArray') || !method_exists($instance, 'name')) {
                continue;
            }

            $descriptor = $instance->toArray();
            $descriptor['name'] = $descriptor['name'] ?? $instance->name();

            $results[] = $descriptor;
        }

        return $results;
    }

    /**
     * @return object|null
     */
    private static function instantiateDescriptor(ReflectionClass $reflection, ?KnowledgeBaseService $knowledgeBase = null)
    {
        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0) {
            return $reflection->newInstance();
        }

        $args = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $type->getName() === KnowledgeBaseService::class) {
                if ($knowledgeBase === null) {
                    return null;
                }
                $args[] = $knowledgeBase;
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            return null;
        }

        return $reflection->newInstanceArgs($args);
    }
}
