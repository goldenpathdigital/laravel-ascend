# Laravel Ascend

Ascend is an MCP-compatible server and toolkit that upgrades Laravel applications with structured upgrade documentation, analyzers, and automation. It ships with a curated Laravel knowledge base, a rich MCP tool suite, and first-class Artisan commands that make it easy to surface the guidance directly in modern IDEs like VS Code via Cline.

## Features
- **MCP-ready server** – launch over stdio via `php artisan ascend:mcp`, exposing tools, resources, and prompts that follow the Model Context Protocol.
- **Curated knowledge base** – search upgrade guides, breaking changes, migration patterns, and upgrade paths sourced from `resources/knowledge-base`.
- **Extensive tool catalog** – documentation queries, dependency scanners, code migration advisors, and package compatibility checks implemented under `src/Tools`.
- **One-command IDE registration** – `ascend:register` writes entries to `.vscode/mcp.json`, `.cursor/mcp.json`, `.junie/mcp/mcp.json`, and Cline’s `cline_mcp_settings.json`.
- **Composer-friendly package** – auto-discovered Laravel service provider registers the Artisan commands needed for MCP integration.

## Requirements
- PHP 7.4+ or 8.x (supports PHP 7.4, 8.0, 8.1, 8.2, 8.3)
- Laravel 6.x - 11.x
- Composer 2.x
- Node.js is **not** required (server runs via PHP/Artisan)

## Installation

### Laravel application
1. Require the package (typically as a dev dependency):
   ```bash
   composer require --dev goldenpathdigital/laravel-ascend
   ```
2. Laravel's package auto-discovery registers the `AscendServiceProvider`, exposing:
   - `php artisan ascend:mcp` – start the MCP server over stdio (add `--kb-path` or `--heartbeat` if needed)
   - `php artisan ascend:register` – register the server with supported MCP clients

## Quick Start

1. **Start the MCP server**
   ```bash
   php artisan ascend:mcp
   ```
   - Runs over stdio (compatible with Cline/Claude Desktop, VSCode MCP).
   - Provide `--kb-path=/path/to/knowledge-base` to point at a custom data directory.
   - Provide `--heartbeat=30` to configure heartbeat interval (default: 30 seconds, min: 10).

   **Connection Keep-Alive:** The server automatically sends heartbeat notifications every 30 seconds (configurable) to prevent idle connection timeouts in MCP clients like VSCode.

2. **Register the server with IDE clients**
   ```bash
   # Laravel Artisan
   php artisan ascend:register
   ```
   Registration automatically updates the following if present:
   
   **Project-level configurations:**
   - `.vscode/mcp.json` (VSCode)
   - `.cursor/mcp.json` (Cursor)
   - `.junie/mcp/mcp.json` (Junie)
   
   **User-level configurations:**
   - `~/.config/Code/User/mcp.json` (VSCode Global)
   - `~/.config/Code/User/globalStorage/saoudrizwan.claude-dev/settings/cline_mcp_settings.json` (Cline)
   - `~/.claude/mcp/mcp.json` (Claude Desktop Legacy)
   - `~/.config/Claude/mcp.json` (Claude Desktop)
   - `~/.config/Codex/mcp.json` (Codex JSON)
   - `~/.codex/config.toml` (Codex TOML)

3. **Connect from your IDE**
   - In VS Code with Cline installed, open the MCP Servers panel and confirm `laravel-ascend` is enabled.
   - For other MCP-aware clients, import the generated config or point them at the running stdio endpoint.

## Artisan Commands

| Command | Description |
| --- | --- |
| `php artisan ascend:mcp [options]` | Start the Ascend MCP server over stdio. |
| `php artisan ascend:register [--global]` | Add Ascend to detected MCP configuration targets (project + global). |

### MCP Server Options

| Option | Description | Default |
| --- | --- | --- |
| `--kb-path=/path` | Path to a custom knowledge base directory | Auto-detected |
| `--heartbeat=30` | Heartbeat interval in seconds (prevents timeout) | 30 seconds |
| `--timeout=900` | Server process timeout in seconds (0 = unlimited) | 900 seconds |
| `--websocket` | Use WebSocket instead of stdio | stdio |
| `--host=127.0.0.1` | Host for WebSocket mode | 127.0.0.1 |
| `--port=8765` | Port for WebSocket mode | 8765 |

## Examples

### Starting the MCP server (stdio mode)
```bash
# Basic usage
php artisan ascend:mcp

# With custom heartbeat interval (recommended for VSCode)
php artisan ascend:mcp --heartbeat=20
```

### Generated Cline entry (excerpt)
```json
{
  "mcpServers": {
    "laravel-ascend": {
      "command": "php",
      "args": [
        "/path/to/your/project/artisan",
        "ascend:mcp"
      ],
      "timeout": 60
    }
  }
}
```

## MCP Tooling Overview
- **Documentation tools** (`src/Tools/Documentation/*`) – search upgrade docs, fetch breaking change details, list deprecated features, and retrieve guided upgrade summaries.
- **Analysis tools** (`src/Tools/Analysis/*`) – inspect current Laravel versions, dependency manifests, PHP compatibility, and breaking changes specific to your code base.
- **Code migration tools** (`src/Tools/Code/*` & `src/Tools/Migration/*`) – analyze configs, facades, Blade templates, generate upgrade checklists, and validate migration steps.
- **Package insights** (`src/Tools/Package/*`) – verify package compatibility and suggest updates that align with target Laravel versions.
- **Tool registry** (`src/Tools/ToolRegistry.php`) – auto-discovers classes implementing `ToolInterface`, so new tools are picked up without manual wiring.

## Knowledge Base Content
The bundled knowledge base lives under `resources/knowledge-base`:
- `index.json` – metadata for the entire data set.
- `breaking-changes/*.json` – per-version breaking change summaries.
- `patterns/*.json` – migration patterns with remediation guidance.
- `upgrade-paths/upgrade-paths.json` – curated upgrade roadmaps (e.g. `8-to-9`).
- `mcp_knowledge_base_guide.md` – authoring guidelines for extending the KB.

To point Ascend at a modified data set, start the Artisan MCP command with `--kb-path=/absolute/path`.

## Development
Clone the repository and install dependencies with Composer, then use the provided scripts:

```bash
composer test           # Pest test suite
composer typecheck      # PHPStan level 8 static analysis
composer format         # php-cs-fixer (PSR-12)
composer coverage       # Pest with coverage (requires Xdebug)
composer mutate         # Infection mutation testing
composer security:audit # Composer advisories
```

The test suite covers knowledge base parsing, MCP registration, and tool behaviors. Please ensure all checks pass before opening a pull request.

## Contributing
See `CODE_OF_CONDUCT.md` for community standards. Security disclosures should follow the instructions in `SECURITY.md`.

## License

Laravel Ascend is open-source software licensed under the [MIT license](LICENSE).
