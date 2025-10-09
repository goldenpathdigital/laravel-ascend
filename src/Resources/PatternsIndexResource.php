<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Resources;

use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use GoldenPathDigital\LaravelAscend\Server\Mcp\Contracts\ResourceInterface;

final class PatternsIndexResource implements ResourceInterface
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
        return 'ascend://knowledge-base/patterns';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $patternIds = $this->knowledgeBase->listPatternIdentifiers();
        
        $patterns = array_map(function (string $patternId) {
            $pattern = $this->knowledgeBase->getPattern($patternId);
            
            return [
                'id' => $patternId,
                'name' => $pattern['name'] ?? $patternId,
                'description' => $pattern['description'] ?? '',
                'versions_affected' => $pattern['versions'] ?? [],
                'uri' => "ascend://patterns/{$patternId}",
            ];
        }, $patternIds);

        return [
            'uri' => $this->name(),
            'name' => 'Migration Patterns Index',
            'description' => 'Index of all common Laravel migration patterns and code transformations',
            'mimeType' => 'application/json',
            'content' => json_encode([
                'total' => count($patterns),
                'patterns' => $patterns,
            ], JSON_PRETTY_PRINT),
        ];
    }
}
