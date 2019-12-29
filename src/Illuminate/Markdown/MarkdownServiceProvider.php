<?php

namespace Illuminate\Markdown;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Markdown\Markdown;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use League\CommonMark\ConverterInterface;
use Michelf\MarkdownInterface;
use Parsedown;
use RuntimeException;

class MarkdownServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Markdown::class, function ($app) {
            if (interface_exists(ConverterInterface::class)) {
                return CommonMarkRenderer::create($app);
            }

            if (class_exists(Parsedown::class)) {
                return ParsedownRenderer::create($app);
            }

            if (interface_exists(MarkdownInterface::class)) {
                return PhpMarkdownRenderer::create($app);
            }

            throw new RuntimeException(
                'Could not create a markdown converter. Please install one of: league/commonmark, erusev/parsedown, michelf/php-markdown.'
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            Markdown::class,
        ];
    }
}
