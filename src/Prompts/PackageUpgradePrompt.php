<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Prompts;

use GoldenPathDigital\LaravelAscend\Server\Mcp\Contracts\PromptInterface;

final class PackageUpgradePrompt implements PromptInterface
{
    public function name(): string
    {
        return 'package-upgrade-guide';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Guidelines for upgrading Laravel ecosystem packages alongside framework upgrades',
            'arguments' => [],
        ];
    }

    public function getContent(): string
    {
        return <<<'MARKDOWN'
# Package Upgrade Guidelines

Managing package compatibility during Laravel framework upgrades.

## Core Principle

**Always update packages before or alongside Laravel upgrades** to avoid compatibility conflicts.

## Package Compatibility Strategy

### 1. First-Party Laravel Packages
These packages typically follow Laravel's versioning:
- **laravel/sanctum** - Authentication
- **laravel/cashier** - Payment processing
- **laravel/scout** - Full-text search
- **laravel/horizon** - Queue management
- **laravel/telescope** - Debugging assistant
- **laravel/passport** - OAuth2 server

**Action**: Update these to match your target Laravel version.

### 2. Commonly Updated Packages

#### Spatie Packages
- **spatie/laravel-permission** - Update for each major Laravel version
- **spatie/laravel-medialibrary** - Check compatibility matrix
- **spatie/laravel-backup** - Usually compatible but verify

#### Testing Packages
- **pestphp/pest** - Keep updated to latest
- **mockery/mockery** - Update for PHP version compatibility
- **nunomaduro/collision** - Update with Laravel version

#### Development Tools
- **barryvdh/laravel-debugbar** - Update with Laravel
- **laravel/pint** - Laravel's code formatter
- **nunomaduro/larastan** - PHPStan wrapper for Laravel

### 3. Check Package Compatibility

Use the `check_package_compatibility` tool to verify each package:
```
check_package_compatibility:
  package: "spatie/laravel-permission"
  target_version: "11.x"
```

### 4. Update Strategy

#### Conservative Approach (Recommended)
1. Update Laravel one version
2. Run tests
3. Update packages one at a time
4. Test after each package update
5. Proceed to next Laravel version

#### Aggressive Approach (Risky)
1. Update all packages to versions compatible with target Laravel
2. Update Laravel
3. Fix all issues at once

## Common Package Issues

### Abandoned Packages
If a package is abandoned:
1. Search for maintained alternatives
2. Check Laravel's native features (many features are now built-in)
3. Consider forking if no alternative exists

### Version Conflicts
When composer reports conflicts:
1. Check `composer why-not laravel/framework 11.x`
2. Identify blocking packages
3. Update or replace blocking packages first
4. Use `suggest_package_updates` tool for recommendations

### Custom Package Compatibility
For internal/custom packages:
1. Review their Laravel dependencies
2. Update service provider registrations if needed
3. Check for deprecated facade usage
4. Update tests to match new Laravel testing helpers

## Package-Specific Upgrade Notes

### Authentication Packages
- **Sanctum**: May require config changes
- **Jetstream**: Often requires major updates between Laravel versions
- **Fortify**: Update with Laravel

### Admin Panels
- **Laravel Nova**: Check compatibility matrix carefully
- **Filament**: Major versions often tied to Laravel versions

### Frontend Integration
- **Inertia.js**: Update both PHP and NPM packages
- **Livewire**: Major versions require careful migration

## Testing After Package Updates

Always test these areas:
1. Authentication flows
2. Database operations (if using query builder packages)
3. File uploads (if using media library packages)
4. Queue jobs (if using specialized queue packages)
5. API endpoints (if using API packages)
6. Admin panels

## Tools to Use

- `check_package_compatibility` - Verify single package compatibility
- `suggest_package_updates` - Get batch update recommendations
- `analyze_dependencies` - Review all package dependencies
- `search_upgrade_docs` - Find package-specific upgrade notes

## Recovery Steps

If package updates break functionality:
1. Revert to previous package version
2. Check package's upgrade guide
3. Look for breaking changes in package changelog
4. Update code to match new package API
5. Test thoroughly before proceeding
MARKDOWN;
    }
}
