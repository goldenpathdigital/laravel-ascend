<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Prompts;

use GoldenPathDigital\LaravelAscend\Server\Mcp\Contracts\PromptInterface;

final class BreakingChangePatternPrompt implements PromptInterface
{
    public function name(): string
    {
        return 'breaking-change-patterns';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Common breaking change patterns and how to handle them during Laravel upgrades',
            'arguments' => [],
        ];
    }

    public function getContent(): string
    {
        return <<<'MARKDOWN'
# Breaking Change Patterns

Recognize and handle common breaking change patterns during Laravel upgrades.

## Pattern Recognition

Use these tools to identify patterns in your codebase:
- `find_usage_patterns` - Search for specific feature usage
- `scan_breaking_changes` - Identify breaking changes for a version
- `get_breaking_change_details` - Get detailed migration instructions

## Context Discipline

- Check tool schemas first to understand parameters and available data; avoid exploratory calls that duplicate existing knowledge.
- When a tool returns data, summarise the actionable fields and reference the summary instead of pasting the full payload.
- Track which breaking changes have already been analysed to prevent re-running the same tool with identical inputs.
- If project files change, note the delta and only re-run the tools affected by those changes.

## Common Breaking Change Patterns

### 1. Method Signature Changes

**Pattern**: Framework methods get new required parameters or change return types

**Identification**:
- PHP errors about wrong parameter counts
- Type errors from mismatched return types

**Resolution**:
1. Use `get_breaking_change_details` for the specific method
2. Update all usages to match new signature
3. Search codebase: `find_usage_patterns` with the method name

**Example**: Laravel 9 - Pagination methods returning different types

### 2. Configuration File Changes

**Pattern**: New required config keys or removed defaults

**Identification**:
- Missing configuration exceptions
- Deprecated config warnings

**Resolution**:
1. Use `analyze_config_changes` tool
2. Compare with fresh Laravel installation config
3. Add missing keys with sensible defaults
4. Remove deprecated keys

### 3. Namespace Changes

**Pattern**: Classes move to new namespaces

**Identification**:
- Class not found errors
- Import statement failures

**Resolution**:
1. Use `check_namespace_changes` tool
2. Update all import statements
3. Check for aliased references in config files
4. Update service provider registrations

**Common Examples**:
- Laravel 8: Models moved from `app/` to `app/Models/`
- Laravel 9: Various internal classes reorganized

### 4. Method/Property Removal

**Pattern**: Framework removes deprecated methods or properties

**Identification**:
- Call to undefined method errors
- Undefined property access

**Resolution**:
1. Search for usage: `find_usage_patterns`
2. Check upgrade guide for replacement: `get_upgrade_guide`
3. Implement alternative approach
4. Test thoroughly

### 5. Facade Changes

**Pattern**: Facade methods change or facades are renamed

**Identification**:
- Facade method not found
- Unexpected behavior from facade calls

**Resolution**:
1. Use `analyze_facades` tool
2. Check if facade is deprecated
3. Update to new facade or underlying service
4. Update imports and references

### 6. Middleware Changes

**Pattern**: Middleware signatures change or middleware is reorganized

**Identification**:
- HTTP kernel errors
- Middleware not executing
- Method signature errors

**Resolution**:
1. Review `app/Http/Kernel.php` changes
2. Update middleware priority if needed
3. Check middleware parameters
4. Test authentication and authorization flows

### 7. Blade Directive Changes

**Pattern**: Blade directives deprecated or syntax changes

**Identification**:
- Blade compilation errors
- Template rendering issues

**Resolution**:
1. Use `scan_blade_templates` tool
2. Update deprecated directives
3. Test all template variations
4. Check nested components

**Common Examples**:
- `@datetime` removed in Laravel 8
- Component syntax changes

### 8. Database/Eloquent Changes

**Pattern**: Query builder or Eloquent API changes

**Identification**:
- Query failures
- Unexpected query results
- Deprecation warnings in tests

**Resolution**:
1. Review model files for deprecated patterns
2. Check for breaking changes in relationships
3. Update eager loading if needed
4. Test database operations thoroughly

### 9. Validation Rule Changes

**Pattern**: Validation rules renamed or behavior changes

**Identification**:
- Validation failures
- Unrecognized rule errors

**Resolution**:
1. Search for validation rule usage
2. Update to new rule names or syntax
3. Test all form validation
4. Check API validation

### 10. Accessor/Mutator Syntax

**Pattern**: Laravel 9+ uses attribute-based accessors/mutators

**Identification**:
- Old `get*Attribute` methods still work but deprecated
- Missing attribute casting

**Resolution**:
1. Find pattern: `find_usage_patterns` with "Attribute"
2. Convert to new Attribute syntax
3. Update related tests
4. Verify computed attributes work correctly

## Systematic Approach to Breaking Changes

### Step 1: Identify
```
scan_breaking_changes:
  from_version: "10.x"
  to_version: "11.x"
```

### Step 2: Prioritize
1. **Critical**: Prevents application from running
2. **High**: Major feature broken
3. **Medium**: Minor feature affected
4. **Low**: Deprecation warning only

### Step 3: Research
```
get_breaking_change_details:
  change_id: "specific-breaking-change"
```

### Step 4: Locate Usage
```
find_usage_patterns:
  pattern: "OldFacade::"
  path: "app/"
```

### Step 5: Apply Fix
Use `get_code_modification_suggestions` for guidance

### Step 6: Validate
```
validate_upgrade_step:
  action: "updated_facade_usage"
  context:
    from: "10.x"
    to: "11.x"
```

### Step 7: Test
- Run automated tests
- Manual testing of affected features
- Check for new deprecation warnings

## Testing Strategy for Breaking Changes

1. **Unit Tests**: Catch method signature changes
2. **Feature Tests**: Catch integration issues
3. **Browser Tests**: Catch frontend/Blade issues
4. **API Tests**: Catch API contract changes

## Documentation References

Use these tools for additional context:
- `search_upgrade_docs` - Find relevant documentation
- `list_deprecated_features` - See what's being removed
- `generate_upgrade_checklist` - Get complete checklist

## Prevention for Future Upgrades

1. Subscribe to Laravel deprecation warnings
2. Fix deprecations immediately, don't accumulate technical debt
3. Keep packages updated regularly
4. Write comprehensive tests
5. Use static analysis tools (PHPStan/Larastan)
MARKDOWN;
    }
}
