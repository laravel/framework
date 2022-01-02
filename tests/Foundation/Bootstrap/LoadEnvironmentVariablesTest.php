<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class LoadEnvironmentVariablesTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['FOO'], $_SERVER['FOO']);
        putenv('FOO');
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
        $this->assertSame('BAR', getenv('FOO'));
        $this->assertSame('BAR', $_ENV['FOO']);
        $this->assertSame('BAR', $_SERVER['FOO']);
    }

    public function testCanFailSilent()
    {
        $this->expectOutputString('');

        (new LoadEnvironmentVariables)->bootstrap($this->getAppMock('BAD_FILE'));
    }
}
