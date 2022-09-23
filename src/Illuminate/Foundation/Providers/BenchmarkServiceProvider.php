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
     * The console output instance, if any.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->app['events']->listen(
                CommandStarting::class,
                fn ($event) => $this->output = $event->output,
            );
        }

        $this->app->singleton(Factory::class, function ($app) {
            $renderer = $this->app->runningInConsole()
                ? new ConsoleRenderer($this->output)
                : new HtmlRenderer();

            return new Factory($renderer);
        });
    }
}
