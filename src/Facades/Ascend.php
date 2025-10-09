<?php

declare(strict_types=1);

namespace GoldenPathDigital\LaravelAscend\Facades;

use GoldenPathDigital\LaravelAscend\Server\AscendServer;
use Illuminate\Support\Facades\Facade;

/**
 * Laravel Facade for Ascend Server.
 *
 * @method static string getServerName()
 * @method static string getServerVersion()
 * @method static array getSupportedProtocolVersions()
 * @method static array getCapabilities()
 * @method static array getKnowledgeBaseInfo()
 * @method static array searchKnowledgeBase(string $query, int $limit = 10)
 * @method static array getBreakingChangeDocument(string $slug)
 * @method static array getBreakingChangeEntry(string $slug, string $changeId)
 * @method static array getPattern(string $patternId)
 * @method static array getUpgradePath(string $identifier)
 * @method static array listUpgradePathIdentifiers()
 * @method static array listPatternIdentifiers()
 * @method static array listBreakingChangeSlugs()
 * @method static array callTool(string $toolName, array $payload = [])
 * @method static array listToolNames()
 * @method static array describeTools()
 * @method static array describeResources()
 * @method static array describePrompts()
 * @method static void registerTool(\GoldenPathDigital\LaravelAscend\Tools\ToolInterface $tool)
 *
 * @see \GoldenPathDigital\LaravelAscend\Server\AscendServer
 */
class Ascend extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return AscendServer::class;
    }
}
