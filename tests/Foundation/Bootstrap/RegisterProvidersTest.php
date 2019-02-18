<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\RegisterProviders;

class RegisterProvidersTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBootstrap()
    {
        $app = m::mock(Application::class);
        $app->shouldReceive('registerConfiguredProviders')->once();

        (new RegisterProviders)->bootstrap($app);
    }
}
