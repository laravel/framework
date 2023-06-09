<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Foundation\Console\DiscoverCommands;
use Illuminate\Tests\Integration\Foundation\Fixtures\CommandDiscovery\Commands\CommandOne;
use Illuminate\Tests\Integration\Foundation\Fixtures\CommandDiscovery\Commands\CommandTwo;
use Orchestra\Testbench\TestCase;

class DiscoverCommandsTest extends TestCase
{
    public function testCommandsCanBeDiscovered()
    {
        class_alias(CommandOne::class, 'Tests\Integration\Foundation\Fixtures\CommandDiscovery\Commands\CommandOne');
        class_alias(CommandTwo::class, 'Tests\Integration\Foundation\Fixtures\CommandDiscovery\Commands\CommandTwo');

        $events = DiscoverCommands::within(__DIR__.'/Fixtures/CommandDiscovery/Commands');

        sort($events);

        $this->assertEquals([
            CommandOne::class,
            CommandTwo::class,
        ], $events);
    }
}
