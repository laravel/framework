<?php

namespace Tests\Feature\Commands;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Console\KeyRotateCommand;
use Illuminate\Foundation\Testing\TestCase;
use Orchestra\Testbench\Concerns\CreatesApplication;

class KeyRotateCommandTest extends TestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->useEnvironmentPath(__DIR__);

        /** @var ConsoleKernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $kernel->registerCommand(new KeyRotateCommand);
    }

    protected function tearDown(): void
    {
        File::delete(__DIR__.DIRECTORY_SEPARATOR.'.env');

        parent::tearDown();
    }

    public function test_command_rotates_keys()
    {
        $currentKey = 'key-1';
        $originalPreviousKeys = ['old-key-1', 'old-key-2'];

        File::put(
            __DIR__.DIRECTORY_SEPARATOR.'.env',
            "APP_KEY=".$currentKey."\nAPP_PREVIOUS_KEYS=".implode(',', $originalPreviousKeys)
        );
        
        Config::set('app.key', $currentKey);
        Config::set('app.previous_keys', $originalPreviousKeys);

        $this->artisan('key:rotate');

        $expectedPreviousKeys = array_merge($originalPreviousKeys, [
           $currentKey
        ]);

        $this->assertEquals($expectedPreviousKeys, Config::get('app.previous_keys'));
        $this->assertNotEquals($currentKey, Config::get('app.key'));
    }
}