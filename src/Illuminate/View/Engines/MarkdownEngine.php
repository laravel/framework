<?php

namespace Illuminate\View\Engines;

use Illuminate\Contracts\View\Engine;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\HtmlString;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Exception\CommonMarkException;
use League\Config\Exception\ConfigurationExceptionInterface;

class MarkdownEngine implements Engine
{
    /**
     * The callback used to render/wrap markdown.
     *
     * @var callable|null
     */
    protected $renderCallback = null;

    /**
     * Create a new markdown engine instance.
     *
     * @param  Filesystem  $files
     * @param  ConverterInterface  $converter
     */
    public function __construct(
        public Filesystem $files,
        public ConverterInterface $converter,
    ) {
    }

    /**
     * Register a callback to be executed when rendering markdown.
     *
     * @param  callable|null  $callback
     * @return void
     */
    public function renderMarkdownUsing($callback)
    {
        $this->renderCallback = $callback;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     *
     * @throws CommonMarkException
     * @throws ConfigurationExceptionInterface
     */
    public function get($path, array $data = [])
    {
        $rendered = $this->converter->convert($this->files->get($path));

        if ($this->renderCallback) {
            $rendered = call_user_func(
                $this->renderCallback, new HtmlString($rendered->getContent()), $rendered, $data, $path
            );
        }

        return (string) $rendered;
    }
}
