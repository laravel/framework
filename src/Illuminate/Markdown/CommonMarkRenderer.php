<?php

namespace Illuminate\Markdown;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Markdown\Markdown;
use Illuminate\Support\HtmlString;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface;
use League\CommonMark\EnvironmentInterface;

class CommonMarkRenderer implements Markdown
{
    /**
     * The CommonMark converter.
     *
     * @var \League\CommonMark\ConverterInterface
     */
    protected $commonmark;

    /**
     * Create a new CommonMark renderer instance.
     *
     * @param  \League\CommonMark\ConverterInterface  $commonmark
     * @return void
     */
    public function __construct(ConverterInterface $commonmark)
    {
        $this->commonmark = $commonmark;
    }

    /**
     * Create a new CommonMark renderer instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return self
     */
    public function create(Container $container)
    {
        if ($container->bound(ConverterInterface::class)) {
            return new CommonMarkRenderer(
                $container->make(ConverterInterface::class)
            );
        }

        if ($container->bound(EnvironmentInterface::class)) {
            return new CommonMarkRenderer(
                new CommonMarkConverter([], $container->make(EnvironmentInterface::class))
            );
        }

        return new CommonMarkRenderer(new CommonMarkConverter);
    }

    /**
     * Render the given Markdown string as HTML.
     *
     * @param  string  $markdown
     * @return \Illuminate\Contracts\Support\Htmlable
     */
    public function render($markdown)
    {
        return new HtmlString(
            rtrim($this->commonmark->convertToHtml($markdown))
        );
    }
}
