<?php

namespace Illuminate\Tests\Integration\Generators;

class ProviderMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Providers/FooServiceProvider.php',
        'app/Providers/DeferredServiceProvider.php',
    ];

    public function testItCanGenerateServiceProviderFile()
    {
        $this->artisan('make:provider', ['name' => 'FooServiceProvider'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Providers;',
            'use Illuminate\Support\ServiceProvider;',
            'class FooServiceProvider extends ServiceProvider',
            'public function register()',
            'public function boot()',
        ], 'app/Providers/FooServiceProvider.php');

        $this->assertContains('App\Providers\FooServiceProvider', require $this->app->getBootstrapProvidersPath());
    }

    public function testItCanGenerateDeferredServiceProviderFile()
    {
        $this->artisan('make:provider', ['name' => 'DeferredServiceProvider', '--deferred' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Providers;',
            'use Illuminate\Contracts\Support\DeferrableProvider;',
            'use Illuminate\Support\ServiceProvider;',
            'class DeferredServiceProvider extends ServiceProvider implements DeferrableProvider',
            'public function register()',
            'public function boot()',
            'public function provides()',
        ], 'app/Providers/DeferredServiceProvider.php');

        $this->assertContains('App\Providers\DeferredServiceProvider', require $this->app->getBootstrapProvidersPath());
    }
}
