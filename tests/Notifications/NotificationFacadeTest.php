<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Container\Container;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\TestCase;

class NotificationFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;

        $container->instance('config', []);
        $container->instance(ChannelManager::class, new ChannelManager($container));

        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }

    public function testNotificationMacro(): void
    {
        $macroName = __FUNCTION__;

        $this->assertFalse(Notification::hasMacro($macroName));

        // Register a macro to test
        Notification::macro($macroName, fn () => true);

        $this->assertTrue(Notification::hasMacro($macroName));
        $this->assertTrue(Notification::$macroName());
    }
}
