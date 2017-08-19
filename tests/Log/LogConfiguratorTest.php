<?php

namespace Illuminate\Tests\Log;

use Mockery as m;
use Monolog\Logger;
use Illuminate\Log\Writer;
use PHPUnit\Framework\TestCase;
use Illuminate\Log\Configurator;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;

class LogConfiguratorTest extends TestCase
{
    public function test_it_configures_a_single_logger()
    {
        $configurator = new Configurator($app = m::mock(Application::class));

        $app->shouldReceive('hasMonologConfigurator')->once()->andReturn(false);
        $app->shouldReceive('bound')->with('config')->andReturn(true);
        $app->shouldReceive('make')->with('config')->andReturn($config = m::mock(Repository::class));
        $config->shouldReceive('get')->with('app.log', 'single')->once()->andReturn('single');
        $config->shouldReceive('get')->with('app.log_channel')->once()->andReturn('test');
        $app->shouldReceive('offsetGet')->with('events')->andReturn(m::mock(Dispatcher::class));
        $app->shouldReceive('storagePath')->andReturn('/test');
        $config->shouldReceive('get')->with('app.log_level', 'debug')->once()->andReturn('debug');

        $this->assertInstanceOf(Writer::class, $configurator->configure());
    }

    public function test_it_configures_a_daily_logger()
    {
        $configurator = new Configurator($app = m::mock(Application::class));

        $app->shouldReceive('hasMonologConfigurator')->once()->andReturn(false);
        $app->shouldReceive('bound')->with('config')->andReturn(true);
        $app->shouldReceive('make')->with('config')->andReturn($config = m::mock(Repository::class));
        $config->shouldReceive('get')->with('app.log', 'single')->once()->andReturn('daily');
        $config->shouldReceive('get')->with('app.log_channel')->once()->andReturn('test');
        $app->shouldReceive('offsetGet')->with('events')->andReturn(m::mock(Dispatcher::class));
        $app->shouldReceive('storagePath')->andReturn('/test');
        $config->shouldReceive('get')->with('app.log_level', 'debug')->once()->andReturn('debug');
        $config->shouldReceive('get')->with('app.log_max_files', 5)->once()->andReturn(0);

        $this->assertInstanceOf(Writer::class, $configurator->configure());
    }

    public function test_it_uses_the_monolog_configurator_if_specified()
    {
        $configurator = new Configurator($app = m::mock(Application::class));

        $app->shouldReceive('hasMonologConfigurator')->once()->andReturn(true);
        $app->shouldReceive('bound')->with('config')->andReturn(true);
        $app->shouldReceive('make')->with('config')->andReturn($config = m::mock(Repository::class));
        $app->shouldReceive('offsetGet')->with('events')->andReturn(m::mock(Dispatcher::class));
        $config->shouldReceive('get')->with('app.log_channel')->once()->andReturn('test');

        $app->shouldReceive('getMonologConfigurator')->once()->andReturn(function ($monolog) {
            $this->assertInstanceOf(Logger::class, $monolog);
        });

        $this->assertInstanceOf(Writer::class, $configurator->configure());
    }
}
