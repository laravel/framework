<?php

namespace Illuminate\Markdown;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Markdown\Markdown;
use Illuminate\Support\HtmlString;
use Parsedown;

class ParsedownRenderer implements Markdown
{
    /**
     * The Parsedown instance.
     *
     * @var \Parsedown
     */
    protected $parsedown;

    /**
     * Create a new Parsedown renderer instance.
     *
     * @param  \Parsedown  $parsedown
     * @return void
     */
    public function __construct(Parsedown $parsedown)
    {
        $this->parsedown = $parsedown;
    }

    /**
     * Create a new Parsedown renderer instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return self
     */
    public function create(Container $container)
    {
        return new ParsedownRenderer($container->make(Parsedown::class));
    }

    /**
     * Render the given markdown string as HTML.
     *
     * @param  string  $markdown
     * @return \Illuminate\Contracts\Support\Htmlable
     */
    public function render($markdown)
    {
        return new HtmlString(
            rtrim($this->parsedown->text($markdown))
        );
    }
}
