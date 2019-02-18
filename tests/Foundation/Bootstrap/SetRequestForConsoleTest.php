<?php

namespace Illuminate\Tests\Foundation\Bootstrap;

use Mockery as m;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Bootstrap\SetRequestForConsole;

class SetRequestForConsoleTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBootstrap()
    {
        $config = m::mock(Repository::class);
        $config
            ->shouldReceive('get')
            ->once()
            ->with('app.url', $url = 'http://localhost')
            ->andReturn($url);

        $app = new Application;
        $app->instance('config', $config);

        (new SetRequestForConsole)->bootstrap($app);

        $this->assertTrue($app->bound('request'));
        $this->assertInstanceof(Request::class, $request = $app->make('request'));
        $this->assertSame($url, $request->url());
        $this->assertSame('GET', $request->method());
    }
}
