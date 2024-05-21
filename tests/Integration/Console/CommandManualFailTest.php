<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Illuminate\Console\ManuallyFailedException;
use Orchestra\Testbench\TestCase;

class CommandManualFailTest extends TestCase
{
    protected function setUp(): void
    {
        Artisan::starting(function ($artisan) {
            $artisan->resolveCommands([
                FailingCommandStub::class,
            ]);
        });

        parent::setUp();
    }

    public function testFailArtisanCommandManually()
    {
        $this->artisan('app:fail')->assertFailed();
    }

    public function testCreatesAnExceptionFromString()
    {
        $this->expectException(ManuallyFailedException::class);
        $command = new Command;
        $command->fail('Whoops!');
    }

    public function testCreatesAnExceptionFromNull()
    {
        $this->expectException(ManuallyFailedException::class);
        $command = new Command;
        $command->fail();
    }
}

class FailingCommandStub extends Command
{
    protected $signature = 'app:fail';

    public function handle()
    {
        $this->trigger_failure();

        // This should never be reached.
        return static::SUCCESS;
    }

    protected function trigger_failure()
    {
        $this->fail('Whoops!');
    }
}
