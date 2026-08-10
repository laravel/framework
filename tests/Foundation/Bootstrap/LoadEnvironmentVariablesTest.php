<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Mockery;
use PHPUnit\Framework\TestCase;

class LoadEnvironmentVariablesTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['FOO'], $_SERVER['FOO']);
        putenv('FOO');
    }

    protected function getAppMock($file)
    {
        $app = Mockery::mock(Application::class);

        $app->expects('configurationIsCached')
            ->with()->andReturn(false);
        $app->expects('runningInConsole')
            ->with()->andReturn(false);
        $app->expects('environmentPath')
            ->with()->andReturn(__DIR__.'/../fixtures');
        $app->expects('environmentFile')
            ->with()->andReturn($file);

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
