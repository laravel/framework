<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class LoadEnvironmentVariablesTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
        putenv('APP_ENV');

        LoadEnvironmentVariables::flushState();
    }

    protected function tearDown(): void
    {
        unset($_ENV['FOO'], $_SERVER['FOO'], $_ENV['APP_ENV'], $_SERVER['APP_ENV'], $_ENV['ENVIRONMENT_FILE'], $_SERVER['ENVIRONMENT_FILE']);
        putenv('FOO');
        putenv('APP_ENV');
        putenv('ENVIRONMENT_FILE');

        LoadEnvironmentVariables::flushState();
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

    public function testDoesNotUseEnvironmentDefinedByAPreviouslyLoadedFile()
    {
        $this->expectOutputString('');

        $file = '.env.custom';

        $app = m::mock(Application::class);

        $app->shouldReceive('configurationIsCached')->andReturn(false);
        $app->shouldReceive('runningInConsole')->andReturn(false);
        $app->shouldReceive('environmentPath')->andReturn(__DIR__.'/../fixtures');
        $app->shouldReceive('environmentFile')->andReturnUsing(function () use (&$file) {
            return $file;
        });
        $app->shouldReceive('loadEnvironmentFrom')->andReturnUsing(function ($value) use (&$file) {
            $file = $value;
        });

        (new LoadEnvironmentVariables)->bootstrap($app);
        (new LoadEnvironmentVariables)->bootstrap($app);

        $this->assertSame('custom', env('ENVIRONMENT_FILE'));
    }
}
