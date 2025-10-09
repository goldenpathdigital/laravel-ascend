<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Resources;

use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use GoldenPathDigital\LaravelAscend\Server\Mcp\Contracts\ResourceInterface;

final class BreakingChangesIndexResource implements ResourceInterface
{
    /** @var KnowledgeBaseService */
    private $knowledgeBase;
    
    public function __construct(
        KnowledgeBaseService $knowledgeBase
    ) {
        $this->knowledgeBase = $knowledgeBase;
    }

    public function name(): string
    {
        return 'ascend://knowledge-base/breaking-changes';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $slugs = $this->knowledgeBase->listBreakingChangeSlugs();
        
        $index = array_map(function (string $slug) {
            $doc = $this->knowledgeBase->getBreakingChangeDocument($slug);
            
            return [
                'slug' => $slug,
                'version' => $doc['version'] ?? 'unknown',
                'title' => $doc['title'] ?? $slug,
                'change_count' => count($doc['breaking_changes'] ?? []),
                'uri' => "ascend://breaking-changes/{$slug}",
            ];
        }, $slugs);

        return [
            'uri' => $this->name(),
            'name' => 'Breaking Changes Index',
            'description' => 'Index of all Laravel breaking change documents by version',
            'mimeType' => 'application/json',
            'content' => json_encode([
                'total' => count($index),
                'versions' => $index,
            ], JSON_PRETTY_PRINT),
        ];
    }
}
