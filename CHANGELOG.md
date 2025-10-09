# Changelog

All notable changes to `laravel-ascend` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2025-10-08

### Added
- Initial release of Laravel Ascend
- MCP server implementation with STDIO protocol (supports MCP protocol 2025-06-18, 2024-11-05, 2024-10-07)
- 15+ analysis tools for Laravel upgrades
- Knowledge base with breaking changes for Laravel 7-12
- Comprehensive upgrade patterns and detection rules
- Artisan commands (`ascend:mcp`, `ascend:register`)
- IDE registration support for VSCode, Cursor, Junie, Cline, Claude Desktop, and Codex (JSON & TOML)
- Cache manager with LRU eviction
- Custom exception hierarchy (CacheException, ConfigException, ToolException)
- Built-in Laravel facade for convenient access
- Thread-safe configuration loading
- Input validation throughout
- Resource cleanup with try-finally blocks
- Secure file permissions (0755)
- Complete API reference (API.md)
- Usage guide with examples (USAGE.md)
- PHPDoc coverage on all public methods
- 40+ comprehensive tests
- PHPStan level 8 compliance
- PHP 7.4+ / 8.x compatibility (supports PHP 7.4, 8.0, 8.1, 8.2, 8.3)
- Laravel 6.x - 11.x support

### Security
- Input validation on all user inputs
- Secure directory permissions
- No dangerous functions (eval, exec, etc.)
- Proper error handling without information leakage

---

## Versioning Strategy

This package follows [Semantic Versioning](https://semver.org/):

- **MAJOR** version when incompatible API changes are made
- **MINOR** version when functionality is added in a backward compatible manner
- **PATCH** version when backward compatible bug fixes are made

## Upgrade Guide

### From 0.x to 1.0 (Future)

When upgrading between versions, always:

1. Review the CHANGELOG for breaking changes
2. Run `composer update goldenpathdigital/laravel-ascend`
3. Publish new config if needed: `php artisan vendor:publish --tag=ascend-config --force`
4. Clear cache: `php artisan config:clear`
5. Run tests to ensure compatibility

## Support Policy

| Version | PHP          | Laravel  | Status      |
|---------|--------------|----------|-------------|
| 0.1.x   | 7.4+ / 8.x   | 6.x-11.x | Development |
| 1.x     | 7.4+ / 8.x   | 6.x-11.x | Planned     |

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute.

## Credits

- [Golden Path Digital](https://github.com/goldenpathdigital)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
