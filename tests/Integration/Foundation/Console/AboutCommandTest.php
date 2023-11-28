<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Testing\Assert;
use Orchestra\Testbench\TestCase;

use function Orchestra\Testbench\remote;

class AboutCommandTest extends TestCase
{
    public function testItCanDisplayAboutCommandAsJson()
    {
        $process = remote('about --json')->mustRun();

        Assert::assertArraySubset([
            'environment' => [
                'application_name' => 'Laravel',
                'php_version' => PHP_VERSION,
                'environment' => 'testing',
                'debug_mode' => true,
                'url' => 'localhost',
                'maintenance_mode' => false,
            ],
            'cache' => [
                'config' => false,
                'events' => false,
                'routes' => false,
            ],
            'drivers' => [
                'broadcasting' => 'log',
                'cache' => 'file',
                'database' => 'testing',
                'logs' => ['single'],
                'mail' => 'smtp',
                'queue' => 'sync',
                'session' => 'file',
            ],
        ], json_decode($process->getOutput(), true));
    }
}
