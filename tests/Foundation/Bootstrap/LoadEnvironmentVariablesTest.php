<?php

namespace Illuminate\Tests\Foundation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Symfony\Component\Console\Input\ArgvInput;

class LoadEnvironmentVariablesTest extends TestCase
{
    protected function tearDown(): void
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

        (new LoadEnvironmentVariables(new ArgvInput))->bootstrap($this->getAppMock('.env'));

        $this->assertSame('BAR', env('FOO'));
        $this->assertSame('BAR', getenv('FOO'));
        $this->assertSame('BAR', $_ENV['FOO']);
        $this->assertSame('BAR', $_SERVER['FOO']);
    }

    public function testCanFailSilent()
    {
        $this->expectOutputString('');

        (new LoadEnvironmentVariables(new ArgvInput))->bootstrap($this->getAppMock('BAD_FILE'));
    }

    public function testCanLoadSpecifiedEnvFile()
    {
        $filename = '.alternative_env';
        $argInput = m::mock(ArgvInput::class);

        $argInput->shouldReceive('hasParameterOption')
            ->once()->with('--env-file')->andReturn(true);
        $argInput->shouldReceive('getParameterOption')
            ->once()->with('--env-file')->andReturn($filename);

        $app = m::mock(Application::class);

        $app->shouldReceive('configurationIsCached')
            ->once()->with()->andReturn(false);
        $app->shouldReceive('runningInConsole')
            ->once()->with()->andReturn(true);
        $app->shouldReceive('environmentPath')
            ->twice()->with()->andReturn(__DIR__.'/../fixtures');
        $app->shouldReceive('loadEnvironmentFrom')
            ->once()->with($filename);
        $app->shouldReceive('environmentFile')
            ->once()->with()->andReturn($filename);

        (new LoadEnvironmentVariables($argInput))->bootstrap($app);
    }
}
