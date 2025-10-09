<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Resources;

use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use GoldenPathDigital\LaravelAscend\Server\Mcp\Contracts\ResourceInterface;

final class KnowledgeBaseSummaryResource implements ResourceInterface
{
    /** @var KnowledgeBaseService */
    private $knowledgeBase;

    /** @var array<string, mixed>|null */
    private ?array $cachedArray = null;

    public function __construct(
        KnowledgeBaseService $knowledgeBase,
    ) {
        $this->knowledgeBase = $knowledgeBase;
    }

    public function name(): string
    {
        return 'ascend://knowledge-base/summary';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->cachedArray !== null) {
            return $this->cachedArray;
        }

        $summary = $this->knowledgeBase->getSummary();

        $this->cachedArray = [
            'uri' => $this->name(),
            'name' => 'Knowledge Base Summary',
            'description' => 'Overview of available Laravel upgrade documentation and breaking changes',
            'mimeType' => 'application/json',
            'content' => json_encode($summary, JSON_PRETTY_PRINT),
        ];

        return $this->cachedArray;
    }
}
