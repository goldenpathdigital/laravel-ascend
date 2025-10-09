<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Server\Mcp;

use GoldenPathDigital\LaravelAscend\Server\AscendServer;

final class McpRequestHandler
{
    /** @var AscendServer */
    private $ascendServer;

    private bool $initialized = false;

    public function __construct(
        AscendServer $ascendServer
    ) {
        $this->ascendServer = $ascendServer;
    }

    public function handleRaw(string $payload): string
    {
        $decoded = json_decode($payload, true);

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return $this->encodeError(null, -32700, 'Parse error: ' . json_last_error_msg());
        }

        if (is_array($decoded) && array_is_list($decoded)) {
            $responses = array_values(array_filter(
                array_map(fn ($request) => $this->handleRequest($request), $decoded),
                static fn ($response) => $response !== null,
            ));

            return $responses === [] ? '' : json_encode($responses, JSON_THROW_ON_ERROR);
        }

        $response = $this->handleRequest($decoded);

        if ($response === null) {
            return '';
        }

        return json_encode($response, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed>|null $request
     *
     * @return array<string, mixed>|null
     */
    public function handleRequest(?array $request): ?array
    {
        $id = $request['id'] ?? null;

        if ($request === null || ($request['jsonrpc'] ?? null) !== '2.0') {
            return $this->errorResponse($id, -32600, 'Invalid Request');
        }

        $method = $request['method'] ?? null;
        $params = $request['params'] ?? [];

        if (!is_string($method)) {
            return $this->errorResponse($id, -32601, 'Method not found');
        }

        if (!array_key_exists('id', $request)) {
            // Notifications are intentionally ignored.
            $this->dispatchNotification($method, $params);

            return null;
        }

        try {
            $result = match ($method) {
                'initialize' => $this->initialize($params),
                'ping' => ['status' => 'ok'],
                'tools/list' => $this->listTools($params),
                'tools/call' => $this->callTool($params),
                'resources/list' => $this->listResources($params),
                'resources/read' => $this->readResource($params),
                'prompts/list' => $this->listPrompts($params),
                default => null,
            };
        } catch (\Throwable $exception) {
            return $this->errorResponse($id, -32603, $exception->getMessage());
        }

        if ($result === null) {
            return $this->errorResponse($id, -32601, 'Method not found');
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function errorResponse(mixed $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    private function encodeError(mixed $id, int $code, string $message): string
    {
        return json_encode($this->errorResponse($id, $code, $message), JSON_THROW_ON_ERROR);
    }

    private function dispatchNotification(string $method, mixed $params): void
    {
        if ($method === 'ping') {
            return;
        }

        if ($method === 'tools/listChanged') {
            return;
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function initialize(array $params): array
    {
        $requestedVersion = $params['protocolVersion'] ?? null;
        $supported = $this->ascendServer->getSupportedProtocolVersions();

        // If a version is requested, validate it's in a reasonable format (YYYY-MM-DD)
        // and accept it even if not explicitly in our list for forward compatibility
        if ($requestedVersion !== null) {
            // Check if it matches the MCP date-based version format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedVersion)) {
                throw new \InvalidArgumentException('Invalid protocol version format. Expected YYYY-MM-DD format.');
            }
            // Use the requested version for forward/backward compatibility
            $protocolVersion = $requestedVersion;
        } else {
            // If no version requested, use our most recent supported version
            $protocolVersion = $supported[0];
        }

        $this->initialized = true;

        return [
            'protocolVersion' => $protocolVersion,
            'serverInfo' => [
                'name' => $this->ascendServer->getServerName(),
                'version' => $this->ascendServer->getServerVersion(),
            ],
            'capabilities' => $this->ascendServer->getCapabilities(),
            'instructions' => $this->ascendServer->getInstructions(),
        ];
    }

    private function ensureInitialized(): void
    {
        if (!$this->initialized) {
            throw new \RuntimeException('Server has not been initialized. Call initialize first.');
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function listTools(array $params): array
    {
        $this->ensureInitialized();

        return [
            'tools' => $this->ascendServer->describeTools(),
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function callTool(array $params): array
    {
        $this->ensureInitialized();

        $toolName = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (!is_string($toolName)) {
            throw new \InvalidArgumentException('Parameter "name" is required.');
        }

        if (!is_array($arguments)) {
            throw new \InvalidArgumentException('Parameter "arguments" must be an object.');
        }

        $result = $this->ascendServer->callTool($toolName, $arguments);
        $isError = isset($result['ok']) ? $result['ok'] === false : false;

        // Convert result to text format as required by MCP spec
        $content = [
            [
                'type' => 'text',
                'text' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ],
        ];

        if (isset($result['stream']) && is_array($result['stream'])) {
            foreach ($result['stream'] as $chunk) {
                $content[] = $chunk;
            }
        }

        return [
            'content' => $content,
            'isError' => $isError,
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function listResources(array $params): array
    {
        $this->ensureInitialized();

        return [
            'resources' => $this->ascendServer->describeResources(),
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function readResource(array $params): array
    {
        $this->ensureInitialized();

        $uri = $params['uri'] ?? null;

        if (!is_string($uri)) {
            throw new \InvalidArgumentException('Parameter "uri" is required.');
        }

        $resource = $this->ascendServer->readResource($uri);

        if ($resource === null) {
            throw new \RuntimeException("Resource not found: {$uri}");
        }

        return [
            'contents' => [
                [
                    'uri' => $resource['uri'] ?? $uri,
                    'mimeType' => $resource['mimeType'] ?? 'text/plain',
                    'text' => $resource['content'] ?? '',
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function listPrompts(array $params): array
    {
        $this->ensureInitialized();

        return [
            'prompts' => $this->ascendServer->describePrompts(),
        ];
    }
}
