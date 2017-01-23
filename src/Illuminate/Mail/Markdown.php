<?php

namespace Illuminate\Mail;

use Parsedown;
use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\View\Factory as ViewFactory;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Illuminate\Contracts\Mail\Markdown as MarkdownContract;

class Markdown implements MarkdownContract
{
    /**
     * The view factory implementation.
     *
     * @var \Illuminate\View\Factory
     */
    protected $view;

    /**
     * The markdown parser callable.
     *
     * @var callable
     */
    protected $parser;

    /**
     * The current theme being used when generating emails.
     *
     * @var string
     */
    protected $theme = 'default';

    /**
     * The registered component paths.
     *
     * @var array
     */
    protected $componentPaths = [];

    /**
     * Create a new Markdown renderer instance.
     *
     * @param  \Illuminate\View\Factory  $view
     * @param  array  $options
     * @param  callable|null  $parser
     * @return void
     */
    public function __construct(ViewFactory $view, array $options = [], callable $parser = null)
    {
        $this->view = $view;

        $this->parser = $parser ?: function ($text) {
            if (! class_exists(Parsedown::class)) {
                throw new RuntimeException('A markdown library is required. You may wish to install erusev/parsedown.');
            }

            (new Parsedown)->text($text);
        };

        $this->theme = Arr::get($options, 'theme', 'default');
        $this->loadComponentsFrom(Arr::get($options, 'paths', []));
    }

    /**
     * Render the Markdown template into HTML.
     *
     * @param  string  $view
     * @param  array  $data
     * @param  \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles|null  $inliner
     * @return \Illuminate\Support\HtmlString
     */
    public function render($view, array $data = [], $inliner = null)
    {
        $this->view->flushFinderCache();

        $contents = $this->view->replaceNamespace(
            'mail', $this->htmlComponentPaths()
        )->make($view, $data)->render();

        return new HtmlString(with($inliner ?: new CssToInlineStyles)->convert(
            $contents, $this->view->make('mail::themes.'.$this->theme)->render()
        ));
    }

    /**
     * Render the Markdown template into HTML.
     *
     * @param  string  $view
     * @param  array  $data
     * @return \Illuminate\Support\HtmlString
     */
    public function renderText($view, array $data = [])
    {
        $this->view->flushFinderCache();

        return new HtmlString(preg_replace("/[\r\n]{2,}/", "\n\n", $this->view->replaceNamespace(
            'mail', $this->markdownComponentPaths()
        )->make($view, $data)->render()));
    }

    /**
     * Parse the given Markdown text into HTML.
     *
     * @param  string  $text
     * @return string
     */
    public function parse($text)
    {
        $parser = $this->parser;

        return new HtmlString($parser($text));
    }

    /**
     * Get the HTML component paths.
     *
     * @return array
     */
    public function htmlComponentPaths()
    {
        return array_map(function ($path) {
            return $path.'/html';
        }, $this->componentPaths());
    }

    /**
     * Get the Markdown component paths.
     *
     * @return array
     */
    public function markdownComponentPaths()
    {
        return array_map(function ($path) {
            return $path.'/markdown';
        }, $this->componentPaths());
    }

    /**
     * Get the component paths.
     *
     * @return array
     */
    protected function componentPaths()
    {
        return array_unique(array_merge($this->componentPaths, [
            __DIR__.'/resources/views',
        ]));
    }

    /**
     * Register new mail component paths.
     *
     * @param  array  $paths
     * @return void
     */
    public function loadComponentsFrom(array $paths = [])
    {
        $this->componentPaths = $paths;
    }
}
