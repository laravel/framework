<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Tests\Integration\Generators\TestCase;

class OptimizeCommandTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ServiceProviderWithOptimize::class];
    }

    public function testCanListenToOptimizingEvent(): void
    {
        $this->withoutDeprecationHandling();

        $this->artisan('optimize')
            ->assertSuccessful()
            ->expectsOutputToContain('ServiceProviderWithOptimize')
            ->expectsOutputToContain('my-package-cache')
            ->expectsOutputToContain('my-package-files');
    }

    public function testCanExcludeCommandsByKey(): void
    {
        $this->artisan('optimize', ['--except' => 'my-package-cache'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my-package-cache')
            ->expectsOutputToContain('my-package-files');
    }

    public function testCanExcludeCommandsByCommand(): void
    {
        $this->artisan('optimize', ['--except' => 'my_package:cache'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my-package-cache')
            ->expectsOutputToContain('my-package-files');
    }

    public function testCanExcludeCommandsRegisteredByClass(): void
    {
        $this->artisan('optimize', ['--except' => 'my_package:cache-files'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my-package-files')
            ->expectsOutputToContain('my-package-cache');
    }
}

class ServiceProviderWithOptimize extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            new ClosureCommand('test-name-without-key', fn () => 0),
            new ClosureCommand('my_package:cache', fn () => 0),
            CacheFilesCommand::class,
        ]);

        $this->optimizes(optimize: 'my_package:cache', key: 'my-package-cache');
        $this->optimizes(optimize: CacheFilesCommand::class, key: 'my-package-files');
        $this->optimizes(optimize: 'test-name-without-key');
    }
}

class CacheFilesCommand extends Command
{
    protected $signature = 'my_package:cache-files';

    public function handle()
    {
        return 0;
    }
}
