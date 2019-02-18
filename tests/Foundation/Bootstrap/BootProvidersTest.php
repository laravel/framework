<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;

class BootProvidersTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBootstrap()
    {
        $app = m::mock(Application::class);
        $app->shouldReceive('boot')->once();

        (new BootProviders)->bootstrap($app);
    }
}
