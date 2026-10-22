<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Console\Command;
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
            ->expectsOutputToContain('my-package-cache')
            ->expectsOutputToContain('my-package-files');
    }

    public function testCanExcludeCommandsByKey(): void
    {
        $this->artisan('optimize:clear', ['--except' => 'my-package-cache'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my-package-cache')
            ->expectsOutputToContain('my-package-files');
    }

    public function testCanExcludeCommandsByCommand(): void
    {
        $this->artisan('optimize:clear', ['--except' => 'my_package:clear-cache'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my-package-cache')
            ->expectsOutputToContain('my-package-files');
    }

    public function testCanExcludeCommandsRegisteredByClass(): void
    {
        $this->artisan('optimize:clear', ['--except' => 'my_package:clear-files'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my-package-files')
            ->expectsOutputToContain('my-package-cache');
    }

    public function testCanExcludeMultipleCommands(): void
    {
        $this->artisan('optimize:clear', ['--except' => 'my-package-cache, my_package:clear-files'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my-package-cache')
            ->doesntExpectOutputToContain('my-package-files');
    }
}

class ServiceProviderWithOptimizeClear extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            new ClosureCommand('my_package:clear-cache', fn () => 0),
            ClearFilesCommand::class,
        ]);

        $this->optimizes(clear: 'my_package:clear-cache', key: 'my-package-cache');
        $this->optimizes(clear: ClearFilesCommand::class, key: 'my-package-files');
    }
}

class ClearFilesCommand extends Command
{
    protected $signature = 'my_package:clear-files';

    public function handle()
    {
        return 0;
    }
}
