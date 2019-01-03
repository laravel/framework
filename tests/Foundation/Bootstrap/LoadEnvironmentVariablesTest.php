<?php

namespace Illuminate\Tests\Foundation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

class LoadEnvironmentVariablesTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    protected function getAppMock($file)
    {
        $app = m::mock(Application::class);

        $app->shouldReceive('configurationIsCached')
            ->once()->with()->andReturn(false);
        $app->shouldReceive('runningInConsole')
            ->once()->with()->andReturn(false);
        $app->shouldReceive('environmentPath')
            ->once()->with()->andReturn(__DIR__.'/../fixtures');
        $app->shouldReceive('environmentFile')
            ->once()->with()->andReturn($file);

        return $app;
    }

    public function testCanLoad()
    {
        $this->expectOutputString('');

        (new LoadEnvironmentVariables)->bootstrap($this->getAppMock('.env'));

        $this->assertSame('BAR', env('FOO'));
    }

    public function testCanFailSilent()
    {
        $this->expectOutputString('');

        (new LoadEnvironmentVariables)->bootstrap($this->getAppMock('BAD_FILE'));
    }
}
