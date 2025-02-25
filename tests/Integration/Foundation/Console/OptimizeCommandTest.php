<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Tests\Integration\Generators\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class OptimizeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected $files = [
        'bootstrap/cache/config.php',
        'bootstrap/cache/events.php',
        'bootstrap/cache/routes-v7.php',
    ];

    protected function getPackageProviders($app): array
    {
        return [ServiceProviderWithOptimize::class];
    }

    public function testCanListenToOptimizingEvent(): void
    {
        $this->withoutDeprecationHandling();

        $this->artisan('optimize')
            ->assertSuccessful()
            ->expectsOutputToContain('my package');
    }

    public function testCanExcludeCommandsByKey(): void
    {
        $this->artisan('optimize', ['--except' => 'my package'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my package');
    }

    public function testCanExcludeCommandsByCommand(): void
    {
        $this->artisan('optimize', ['--except' => 'my_package:cache'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my_package:cache');
    }
}

class ServiceProviderWithOptimize extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            new ClosureCommand('my_package:cache', fn () => 0),
        ]);

        $this->optimizes(
            optimize: 'my_package:cache',
            key: 'my package',
        );
    }
}
