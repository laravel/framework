<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Foundation\Cloud\LineFormatter;
use Illuminate\Foundation\CloudBootstrapper;
use Monolog\Formatter\JsonFormatter;
use Orchestra\Testbench\TestCase;

class CloudBootstrapperTest extends TestCase
{
    public function testItConfiguresTheCloudJsonLoggingFormatter()
    {
        CloudBootstrapper::configureCloudLogging($this->app);

        $this->assertSame(JsonFormatter::class, $this->app['config']->get('logging.channels.laravel-cloud-socket.formatter'));
        $this->assertSame([
            'includeStacktraces' => true,
        ], $this->app['config']->get('logging.channels.laravel-cloud-socket.formatter_with'));
    }

    public function testItConfiguresTheCloudLineLoggingFormatter()
    {
        $_SERVER['LARAVEL_CLOUD_LOG_FORMAT'] = 'line';

        CloudBootstrapper::configureCloudLogging($this->app);

        $this->assertSame(LineFormatter::class, $this->app['config']->get('logging.channels.laravel-cloud-socket.formatter'));
        $this->assertSame([], $this->app['config']->get('logging.channels.laravel-cloud-socket.formatter_with'));

        unset($_SERVER['LARAVEL_CLOUD_LOG_FORMAT']);
    }
}
