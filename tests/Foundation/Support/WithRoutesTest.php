<?php

namespace Illuminate\Tests\Foundation\Support;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Support\Providers\WithRoutes;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;

class WithRoutesTest extends TestCase
{
    public function testWithRoutesWillAddBootedCallback()
    {
        $app = new Application();
        $provider = new ServiceProviderWithRoutesStub($app);
        $app->register($provider);

        $r = new \ReflectionClass($provider);
        $p = $r->getProperty('bootedCallbacks');
        $p->setAccessible(true);

        $this->assertCount(1, $p->getValue($provider));
    }
}

class ServiceProviderWithRoutesStub extends ServiceProvider
{
    use WithRoutes;

    public $namespace = '';
}
