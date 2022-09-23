<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Foundation\Benchmark\Factory;
use Illuminate\Foundation\Benchmark\Renderers\ConsoleRenderer;
use Illuminate\Foundation\Benchmark\Renderers\HtmlRenderer;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Output\ConsoleOutput;

class BenchmarkServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Factory::class, function ($app) {
            $renderer = $this->app->runningInConsole()
                ? new ConsoleRenderer(new ConsoleOutput())
                : new HtmlRenderer();

            return new Factory($renderer);
        });
    }
}
