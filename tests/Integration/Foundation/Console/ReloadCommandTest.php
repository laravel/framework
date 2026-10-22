<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Tests\Integration\Generators\TestCase;

class ReloadCommandTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ServiceProviderWithReload::class];
    }

    public function testCanListenToReloadingEvent(): void
    {
        $this->withoutDeprecationHandling();

        $this->artisan('reload')
            ->assertSuccessful()
            ->expectsOutputToContain('my-package-workers')
            ->expectsOutputToContain('my-package-sockets');
    }

    public function testCanExcludeCommandsByKey(): void
    {
        $this->artisan('reload', ['--except' => 'my-package-workers'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my-package-workers')
            ->expectsOutputToContain('my-package-sockets');
    }

    public function testCanExcludeCommandsByCommand(): void
    {
        $this->artisan('reload', ['--except' => 'my_package:reload-workers'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my-package-workers')
            ->expectsOutputToContain('my-package-sockets');
    }

    public function testCanExcludeCommandsRegisteredByClass(): void
    {
        $this->artisan('reload', ['--except' => 'my_package:reload-sockets'])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('my-package-sockets')
            ->expectsOutputToContain('my-package-workers');
    }
}

class ServiceProviderWithReload extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            new ClosureCommand('my_package:reload-workers', fn () => 0),
            ReloadSocketsCommand::class,
        ]);

        $this->reloads('my_package:reload-workers', key: 'my-package-workers');
        $this->reloads(ReloadSocketsCommand::class, key: 'my-package-sockets');
    }
}

class ReloadSocketsCommand extends Command
{
    protected $signature = 'my_package:reload-sockets';

    public function handle()
    {
        return 0;
    }
}
