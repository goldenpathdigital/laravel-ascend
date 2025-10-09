# Contributing to Laravel Ascend

Thank you for considering contributing to Laravel Ascend! This document outlines the process and guidelines for contributing.

## Code of Conduct

By participating in this project, you agree to abide by the [Code of Conduct](CODE_OF_CONDUCT.md).

## How Can I Contribute?

### Reporting Bugs

Before creating a bug report, please check existing issues to avoid duplicates.

**When submitting a bug report, include:**

- Laravel version
- PHP version  
- Package version
- Clear description of the issue
- Steps to reproduce
- Expected vs actual behavior
- Error messages/stack traces
- Relevant code snippets

**Use this template:**

```markdown
**Environment:**
- Laravel: 11.x
- PHP: 8.2
- Package: 0.1.0

**Description:**
[Clear description of the bug]

**Steps to Reproduce:**
1. Step one
2. Step two
3. ...

**Expected Behavior:**
[What you expected to happen]

**Actual Behavior:**
[What actually happened]

**Error Output:**
```
[Error messages or stack traces]
```
```

### Suggesting Enhancements

Enhancement suggestions are welcome! Please provide:

- Clear use case
- Why this would be valuable
- Possible implementation approach
- Examples from other packages (if applicable)

### Pull Requests

**Before submitting:**

1. Check existing PRs to avoid duplicates
2. Discuss major changes in an issue first
3. Ensure all tests pass
4. Add tests for new functionality
5. Update documentation

**PR Checklist:**

- [ ] Tests added/updated
- [ ] Documentation updated (API.md, USAGE.md)
- [ ] CHANGELOG.md updated
- [ ] PHPStan passes (level 8)
- [ ] Code follows style guidelines
- [ ] Commit messages are clear

## Development Setup

### Requirements

- PHP 7.4 or higher (7.4, 8.0, 8.1, 8.2, 8.3 supported)
- Composer
- Git

### Setup Instructions

```bash
# Clone the repository
git clone https://github.com/goldenpathdigital/laravel-ascend.git
cd laravel-ascend

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer typecheck

# Run code formatting
composer format
```

## Coding Standards

### PHP Standards

- Follow PSR-12 coding standards
- Use strict types: `declare(strict_types=1);`
- Write code compatible with PHP 7.4+ (avoid PHP 8.1+ exclusive features like `readonly`, constructor property promotion)
- Use PHPDoc annotations for `mixed` types (not available in PHP 7.4)
- Type-hint parameters and return types where possible (avoid `mixed` type hint)

### Code Style

This project uses PHP CS Fixer. Run before committing:

```bash
composer format
```

### Static Analysis

Code must pass PHPStan level 8:

```bash
composer typecheck
```

### Documentation

- All public methods must have PHPDoc blocks
- Include parameter types and descriptions
- Document exceptions that may be thrown
- Provide code examples for complex features

**PHPDoc Example:**

```php
/**
 * Store a value in the cache with optional TTL.
 *
 * @param string $key The cache key (alphanumeric with ._-: allowed, max 255 chars)
 * @param mixed $value The value to cache (will be serialized)
 * @param int|null $ttl Time-to-live in seconds (uses default if null)
 * @throws CacheException If key is invalid or value exceeds max size
 */
public function set(string $key, mixed $value, ?int $ttl = null): void
```

## Testing Guidelines

### Writing Tests

- Use Pest testing framework
- One test class per source class
- Test happy path and edge cases
- Test error conditions
- Use descriptive test names

**Test Example:**

```php
test('cache manager stores and retrieves values', function () {
    $cache = new CacheManager();
    
    $cache->set('test-key', 'test-value');
    
    expect($cache->get('test-key'))->toBe('test-value');
    expect($cache->has('test-key'))->toBeTrue();
});
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
./vendor/bin/pest tests/Cache/CacheManagerTest.php

# Run with coverage
composer coverage
```

### Test Coverage

- Aim for 80%+ code coverage
- All new features must include tests
- Bug fixes should include regression tests

## Git Workflow

### Branch Naming

- `feature/description` - New features
- `fix/description` - Bug fixes
- `docs/description` - Documentation updates
- `refactor/description` - Code refactoring
- `test/description` - Test additions/updates

### Commit Messages

Follow conventional commits format:

```
type(scope): subject

body (optional)

footer (optional)
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Test additions/updates
- `chore`: Build/tooling changes

**Examples:**

```
feat(cache): add TTL support for cache entries

fix(config): prevent race condition in config loading

docs(api): add examples for tool usage

test(cache): add tests for cache eviction
```

## Adding New Features

### New Tools

1. Create tool class extending `AbstractTool`
2. Implement required methods
3. Add tool tests
4. Document in API.md and USAGE.md
5. Update CHANGELOG.md

**Tool Example:**

```php
<?php

namespace GoldenPathDigital\LaravelAscend\Tools\Analysis;

use GoldenPathDigital\LaravelAscend\Tools\AbstractTool;

final class MyNewTool extends AbstractTool
{
    public function getName(): string
    {
        return 'my_new_tool';
    }
    
    public function getDescription(): string
    {
        return 'Description of what this tool does';
    }
    
    public function execute(array $payload): array
    {
        $startedAt = microtime(true);
        
        try {
            // Your logic here
            
            return $this->success([
                'result' => 'data',
            ], [], $startedAt);
        } catch (\Exception $e) {
            return $this->error(
                $e->getMessage(),
                [],
                $startedAt
            );
        }
    }
    
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'param1' => [
                    'type' => 'string',
                    'description' => 'Parameter description',
                ],
            ],
            'required' => ['param1'],
        ];
    }
}
```

### New Knowledge Base Entries

1. Add JSON file to appropriate directory
2. Follow existing schema
3. Add tests
4. Update documentation

## Release Process

Releases are managed by maintainers. The process:

1. Update version in `composer.json`
2. Update CHANGELOG.md with release date
3. Create git tag
4. Push tag to trigger release

## Questions?

- Open a [discussion](https://github.com/goldenpathdigital/laravel-ascend/discussions)
- Check existing [issues](https://github.com/goldenpathdigital/laravel-ascend/issues)
- Read the [documentation](README.md)

## Recognition

Contributors will be recognized in:
- CHANGELOG.md
- GitHub contributors page
- Package credits

Thank you for contributing to Laravel Ascend! 🚀
