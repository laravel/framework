<?php

namespace Illuminate\Markdown;

use Illuminate\Contracts\Container\Container;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface;
use Michelf\Markdown as PhpMarkdown;
use Michelf\MarkdownInterface;
use Parsedown;
use RuntimeException;

class MarkdownLocator
{
    /**
     * Create a new markdown renderer instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return \Illuminate\Contracts\Markdown\Markdown
     *
     * @throws \RuntimeException
     */
    public static function create(Container $container)
    {
        if (interface_exists(ConverterInterface::class)) {
            return new CommonMarkRenderer($container->make(
                $container->bound(ConverterInterface::class) ? ConverterInterface::class : CommonMarkConverter::class
            ));
        }

        if (class_exists(Parsedown::class)) {
            return new ParsedownRenderer(
                $container->make(Parsedown::class)
            );
        }

        if (interface_exists(MarkdownInterface::class)) {
            return new PhpMarkdownRenderer($container->make(
                $container->bound(MarkdownInterface::class) ? MarkdownInterface::class : PhpMarkdown::class
            ));
        }

        throw new RuntimeException(
            'Could not create a markdown converter. Please install one of: league/commonmark, erusev/parsedown, michelf/php-markdown.'
        );
    }
}
