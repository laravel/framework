<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Testing\Assert;
use Orchestra\Testbench\Attributes\WithEnv;
use Orchestra\Testbench\TestCase;

use function Orchestra\Testbench\remote;

#[WithEnv('APP_MAINTENANCE_STORE', 'array')]
class AboutCommandTest extends TestCase
{
    public function testItCanDisplayAboutCommandAsJson()
    {
        $process = remote('about --json')->mustRun();

        tap(json_decode($process->getOutput(), true), function ($output) {
            Assert::assertArraySubset([
                'application_name' => 'Laravel',
                'php_version' => PHP_VERSION,
                'environment' => 'testing',
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
                'mail' => 'smtp',
                'queue' => 'database',
                'session' => 'database',
            ], $output['drivers']);
        });
    }
}
