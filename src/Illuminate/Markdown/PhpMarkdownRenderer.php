<?php

namespace Illuminate\Markdown;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Markdown\Markdown;
use Illuminate\Support\HtmlString;
use Michelf\Markdown as PhpMarkdown;
use Michelf\MarkdownInterface;

class PhpMarkdownRenderer implements Markdown
{
    /**
     * The PHP Markdown parser.
     *
     * @var \Michelf\MarkdownInterface
     */
    protected $markdown;

    /**
     * Create a new PHP Markdown renderer instance.
     *
     * @param  \Michelf\MarkdownInterface  $markdown
     * @return void
     */
    public function __construct(MarkdownInterface $markdown)
    {
        $this->markdown = $markdown;
    }

    /**
     * Create a new PHP Markdown renderer instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return self
     */
    public function create(Container $container)
    {
        if ($container->bound(MarkdownInterface::class)) {
            return new PhpMarkdownRenderer($container->make(MarkdownInterface::class));
        }

        return new PhpMarkdownRenderer(new PhpMarkdown);
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
            rtrim($this->markdown->transform($markdown))
        );
    }
}
