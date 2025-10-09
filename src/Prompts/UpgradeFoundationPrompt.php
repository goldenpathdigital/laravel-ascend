<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Prompts;

use GoldenPathDigital\LaravelAscend\Server\Mcp\Contracts\PromptInterface;

final class UpgradeFoundationPrompt implements PromptInterface
{
    public function name(): string
    {
        return 'upgrade-foundation';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Foundational guidelines for Laravel framework upgrades',
            'arguments' => [],
        ];
    }

    public function getContent(): string
    {
        return <<<'MARKDOWN'
# Laravel Upgrade Guidelines

These guidelines help ensure safe, systematic Laravel framework upgrades.

## Foundational Principles

1. **Always upgrade incrementally** - Never skip major versions (e.g., don't jump from Laravel 8 to 11 directly)
2. **Test thoroughly** - Run your test suite after each incremental upgrade
3. **Review breaking changes** - Use the knowledge base to identify all breaking changes for your upgrade path
4. **Update dependencies first** - Ensure all composer packages are compatible with the target Laravel version
5. **Back up your project** - Always have a backup or use version control before starting

## Context Discipline

- Inspect tool schemas via `describe_tools` before calling anything; confirm required parameters.
- Call tools only when information is missing from the current discussion; reference earlier outputs instead of re-running them.
- Summarise tool results in a few actionable bullets and persist key identifiers (e.g., upgrade path IDs) for later steps.
- Defer expensive file-scanning tools (e.g., pattern searches, Blade scans) until you are ready to make code changes in that area of the plan.
- When you do run scans, narrow their scope (specific directories/globs) so outputs stay focused and manageable.
- Avoid pasting entire JSON payloads into replies—extract only the fields needed to progress.
- Note when results become stale (project updated, dependencies changed) and refresh tools selectively.
- Record each tool invocation in your working notes (name, inputs, summary) to avoid redundant calls later in the process.

## Upgrade Process

### Phase 1: Analysis
1. Determine current Laravel version using `analyze_current_version` tool
2. Identify upgrade path to target version using `get_upgrade_path` tool
3. Check PHP version compatibility using `check_php_compatibility` tool
4. Scan for breaking changes using `scan_breaking_changes` tool

### Phase 2: Preparation
1. Review all breaking changes for the next version
2. Check package compatibility using `check_package_compatibility` tool
3. Update composer.json with new version constraints
4. Run composer update for the next version only

### Phase 3: Code Migration
1. Address each breaking change systematically
2. Use code modification suggestions from the knowledge base
3. Update deprecated features
4. Modernize code patterns when appropriate

### Phase 4: Validation
1. Run your test suite
2. Manually test critical functionality
3. Validate that all breaking changes are addressed using `validate_upgrade_step` tool
4. Review deprecation warnings for future versions

### Phase 5: Iteration
Repeat phases 1-4 for each incremental version until you reach your target.

## Best Practices

- **Use feature tests** - They catch integration issues that unit tests might miss
- **Update one version at a time** - Don't update multiple major versions in a single commit
- **Read upgrade guides** - Use `get_upgrade_guide` tool to access official documentation
- **Check config changes** - Use `analyze_config_changes` tool to identify required configuration updates
- **Review Blade templates** - Use `scan_blade_templates` tool to find deprecated directives
- **Update namespaces** - Use `check_namespace_changes` tool to identify namespace modifications

## Common Pitfalls to Avoid

- Skipping intermediate versions
- Not reading the upgrade guide completely
- Ignoring deprecation warnings
- Not testing thoroughly before moving to the next version
- Forgetting to update published config files
- Not checking for custom package compatibility

## When Things Go Wrong

If you encounter issues:
1. Check the breaking changes documentation for that specific version
2. Use `search_upgrade_docs` tool to find solutions for specific errors
3. Review similar issues in the Laravel upgrade guides
4. Validate each completed step using the validation tools
5. Consider rolling back to the previous working version if needed

## Additional Resources

- Use `list_deprecated_features` to see what will be removed in future versions
- Use `find_usage_patterns` to locate specific feature usage in your codebase
- Generate a complete checklist using `generate_upgrade_checklist` tool
MARKDOWN;
    }
}
