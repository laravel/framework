<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Support\Env;
use JMac\Testing\Double;
use PHPUnit\Framework\TestCase;

class LoadEnvironmentVariablesTest extends TestCase
{
    protected function setUp(): void
    {
        // Testbench disables putenv and may not re-enable it.
        Env::enablePutenv();
    }

    protected function tearDown(): void
    {
        unset($_ENV['FOO'], $_SERVER['FOO']);
        putenv('FOO');
    }

    protected function getAppMock($file)
    {
        $app = Double::for(Application::class, override: true);

        $app->expects('configurationIsCached')->with()->returns(false);
        $app->expects('runningInConsole')->with()->returns(false);
        $app->expects('environmentPath')->with()->returns(__DIR__.'/../Fixtures');
        $app->expects('environmentFile')->with()->returns($file);

        return $app->instance();
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
