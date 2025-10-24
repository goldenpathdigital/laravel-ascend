<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Console;

use function config;

use GoldenPathDigital\LaravelAscend\Server\AscendServer;
use GoldenPathDigital\LaravelAscend\Server\Mcp\McpRequestHandler;
use GoldenPathDigital\LaravelAscend\Server\Mcp\McpStdioServer;
use GoldenPathDigital\LaravelAscend\Server\Mcp\McpWebSocketServer;
use Illuminate\Console\Command;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

use function set_time_limit;

class ServeCommand extends Command
{
    protected $signature = 'ascend:mcp
        {--host=127.0.0.1 : Host for WebSocket mode}
        {--port=8765 : Port for WebSocket mode}
        {--websocket : Serve over WebSocket instead of stdio}
        {--stdio : Explicitly force stdio mode (default)}
        {--kb-path= : Path to a custom knowledge base directory}
        {--timeout= : Override server timeout in seconds (0 disables)}
        {--heartbeat=30 : Heartbeat interval in seconds for stdio mode (default: 30)}';

    protected $description = 'Start the Ascend MCP server (stdio by default, WebSocket optional).';

    public function handle(): int
    {
        $knowledgeBasePath = $this->option('kb-path');
        $server = AscendServer::createDefault($knowledgeBasePath ?: null);

        $timeoutOption = $this->option('timeout');
        $maxRuntime = (int) config('ascend.server.max_runtime', 900);

        if ($timeoutOption !== null && $timeoutOption !== '') {
            if (!is_numeric($timeoutOption)) {
                $this->error('Timeout must be a non-negative integer.');

                return self::FAILURE;
            }

            $maxRuntime = (int) $timeoutOption;
        }

        if ($maxRuntime < 0) {
            $this->error('Timeout must be a non-negative integer.');

            return self::FAILURE;
        }

        if ($maxRuntime <= 0) {
            set_time_limit(0);
        } else {
            set_time_limit($maxRuntime);
        }

        $useWebsocket = (bool) $this->option('websocket');
        $forceStdio = (bool) $this->option('stdio');

        if ($useWebsocket && $forceStdio) {
            $this->error('Cannot enable both stdio and WebSocket modes simultaneously.');

            return self::FAILURE;
        }

        if (!$useWebsocket) {
            $heartbeatInterval = (int) ($this->option('heartbeat') ?? 30);
            if ($heartbeatInterval < 10) {
                $this->warn('Heartbeat interval must be at least 10 seconds. Using 10.');
                $heartbeatInterval = 10;
            }

            $handler = new McpRequestHandler($server);
            (new McpStdioServer($handler, $heartbeatInterval))->run();

            return self::SUCCESS;
        }

        $host = (string) $this->option('host');
        $port = (int) $this->option('port');

        $logger = function (string $message): void {
            $this->info($message);
        };

        $component = new McpWebSocketServer($server, $logger(...));
        $httpServer = new HttpServer(new WsServer($component));
        $socketServer = IoServer::factory($httpServer, $port, $host);

        $logger(sprintf('Ascend MCP server listening at ws://%s:%d', $host, $port));

        $socketServer->run();

        return self::SUCCESS;
    }
}
