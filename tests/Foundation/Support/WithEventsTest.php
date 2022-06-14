<?php

namespace Illuminate\Tests\Foundation\Support;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Support\Providers\WithEvents;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;

class WithEventsTest extends TestCase
{
    public function testWithEventsWillAddBootingCallbacks()
    {
        $app = new Application();
        $app->registerCoreContainerAliases();
        $provider = new ServiceProviderWithEventsStub($app);
        $app->register($provider);

        $r = new \ReflectionClass($provider);
        $p = $r->getProperty('bootingCallbacks');
        $p->setAccessible(true);

        $this->assertCount(1, $p->getValue($provider));
    }
}

class ServiceProviderWithEventsStub extends ServiceProvider
{
    use WithEvents;
}
