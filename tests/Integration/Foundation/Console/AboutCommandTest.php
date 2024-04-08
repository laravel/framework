<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Testing\Assert;
use Orchestra\Testbench\TestCase;

use function Orchestra\Testbench\remote;

class AboutCommandTest extends TestCase
{
    public function testItCanDisplayAboutCommandAsJson()
    {
        $process = remote('about --json', ['APP_ENV' => 'local'])->mustRun();

        tap(json_decode($process->getOutput(), true), function ($output) {
            Assert::assertArraySubset([
                'application_name' => 'Laravel',
                'php_version' => PHP_VERSION,
                'environment' => 'local',
                'debug_mode' => true,
                'url' => 'localhost',
                'maintenance_mode' => false,
            ], $output['environment']);

            Assert::assertArraySubset([
                'config' => false,
                'events' => false,
                'routes' => false,
            ], $output['cache']);

            Assert::assertArraySubset([
                'broadcasting' => 'log',
                'cache' => 'database',
                'database' => 'testing',
                'logs' => ['single'],
                'mail' => 'log',
                'queue' => 'database',
                'session' => 'cookie',
            ], $output['drivers']);
        });
    }
}
