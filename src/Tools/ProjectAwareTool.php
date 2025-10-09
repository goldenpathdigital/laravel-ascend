<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Tools;

use GoldenPathDigital\LaravelAscend\Analyzers\FilesystemScanner;
use GoldenPathDigital\LaravelAscend\Analyzers\PatternAnalyzer;
use GoldenPathDigital\LaravelAscend\Analyzers\ProjectAnalyzer;
use GoldenPathDigital\LaravelAscend\Analyzers\ProjectContext;
use GoldenPathDigital\LaravelAscend\Documentation\KnowledgeBaseService;
use function base_path;

abstract class ProjectAwareTool extends AbstractTool
{
    /** @var KnowledgeBaseService */
    protected $knowledgeBase;

    protected ProjectAnalyzer $projectAnalyzer;

    public function __construct(
        KnowledgeBaseService $knowledgeBase
    ) {
        $this->knowledgeBase = $knowledgeBase;
        $this->projectAnalyzer = new ProjectAnalyzer($knowledgeBase);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function createContext(array $payload): ProjectContext
    {
        $root = $payload['project_root'] ?? base_path();

        return new ProjectContext((string) $root);
    }

    protected function createScanner(ProjectContext $context): FilesystemScanner
    {
        return new FilesystemScanner($context);
    }

    protected function createPatternAnalyzer(ProjectContext $context): PatternAnalyzer
    {
        return new PatternAnalyzer($this->knowledgeBase, $this->createScanner($context));
    }
}
