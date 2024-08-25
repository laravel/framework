<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LoadEnvironmentVariablesTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['FOO'], $_SERVER['FOO']);
        putenv('FOO');
        m::close();
    }

    protected function getAppMock($file, $n = 1)
    {
        $app = m::mock(Application::class)->makePartial();

        $app->shouldReceive('configurationIsCached')
            ->times($n)->with()->andReturn(false);
        $app->shouldReceive('runningInConsole')
            ->times($n)->with()->andReturn(false);
        $app->shouldReceive('environmentPath')
            ->atLeast($n)->with()->andReturn(__DIR__.'/../fixtures');

        $app->loadEnvironmentFrom($file);

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

    #[DataProvider('appEnvProvider')]
    public function testIdempotence(?string $appEnv, string $expected): void
    {
        if (isset($appEnv)) {
            $_ENV['APP_ENV'] = $appEnv;
        }

        $app = $this->getAppMock('.env.idempotence', 2);

        (new LoadEnvironmentVariables)->bootstrap($app);

        $this->assertSame($expected, env('FOO'));
        $this->assertSame($expected, getenv('FOO'));
        $this->assertSame($expected, $_ENV['FOO']);
        $this->assertSame($expected, $_SERVER['FOO']);

        (new LoadEnvironmentVariables)->bootstrap($app);

        $this->assertSame($expected, env('FOO'));
        $this->assertSame($expected, getenv('FOO'));
        $this->assertSame($expected, $_ENV['FOO']);
        $this->assertSame($expected, $_SERVER['FOO']);

        $reflection = new \ReflectionClass(LoadEnvironmentVariables::class);
        $reflection->setStaticPropertyValue('externallyProvidedAppEnv', null);

        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
        putenv('APP_ENV');
    }

    public static function appEnvProvider(): array
    {
        return [
            'Use default .env file.' => ['appEnv' => null, 'expected' => 'BAR'],
            'APP_ENV has been externally provided.' => ['appEnv' => 'baz', 'expected' => 'BAZ'],
        ];
    }
}
