<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Resources;

use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use GoldenPathDigital\LaravelAscend\Server\Mcp\Contracts\ResourceInterface;

final class UpgradePathsResource implements ResourceInterface
{
    /** @var KnowledgeBaseService */
    private $knowledgeBase;

    public function __construct(
        KnowledgeBaseService $knowledgeBase,
    ) {
        $this->knowledgeBase = $knowledgeBase;
    }

    public function name(): string
    {
        return 'ascend://knowledge-base/upgrade-paths';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $identifiers = $this->knowledgeBase->listUpgradePathIdentifiers();

        $paths = array_map(function (string $identifier) {
            $path = $this->knowledgeBase->getUpgradePath($identifier);

            return [
                'identifier' => $identifier,
                'from_version' => $path['from'] ?? 'unknown',
                'to_version' => $path['to'] ?? 'unknown',
                'steps' => $path['steps'] ?? [],
                'estimated_complexity' => $path['complexity'] ?? 'medium',
                'uri' => "ascend://upgrade-paths/{$identifier}",
            ];
        }, $identifiers);

        return [
            'uri' => $this->name(),
            'name' => 'Upgrade Paths Index',
            'description' => 'Index of all available Laravel upgrade paths with step-by-step guidance',
            'mimeType' => 'application/json',
            'content' => json_encode([
                'total' => count($paths),
                'paths' => $paths,
            ], JSON_PRETTY_PRINT),
        ];
    }
}
