<?php

namespace Illuminate\Foundation\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Foundation\Benchmark\Factory;
use Illuminate\Foundation\Benchmark\Renderers\ConsoleRenderer;
use Illuminate\Foundation\Benchmark\Renderers\HtmlRenderer;
use Illuminate\Support\ServiceProvider;

class BenchmarkServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Factory::class, function ($app) {
            if ($this->app->runningInConsole()) {
                $renderer = new ConsoleRenderer();

                $this->app['events']->listen(
                    CommandStarting::class,
                    fn ($event) => $renderer->setOutput($event->output)
                );

                return new Factory(new ConsoleRenderer());
            }

            return new Factory(new HtmlRenderer());
        });
    }
}
