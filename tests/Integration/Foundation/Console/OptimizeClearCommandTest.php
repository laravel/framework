<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Tests\Integration\Generators\TestCase;

class OptimizeClearCommandTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ServiceProviderWithOptimizeClear::class];
    }

    public function testCanListenToOptimizingEvent(): void
    {
        $this->withoutDeprecationHandling();

        $this->artisan('optimize:clear')
            ->assertSuccessful()
            ->expectsOutputToContain('ServiceProviderWithOptimizeClear');
    }

    public function testCanExcludeCommandsByKey(): void
    {
        $this->artisan('optimize:clear', ['--except' => 'my package'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my package');
    }

    public function testCanExcludeCommandsByCommand(): void
    {
        $this->artisan('optimize:clear', ['--except' => 'my_package:cache'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my_package:cache');
    }
}

class ServiceProviderWithOptimizeClear extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            new ClosureCommand('my_package:clear', fn () => 0),
        ]);

        $this->optimizes(
            clear: 'my_package:clear',
        );
    }
}
