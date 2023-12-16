<?php

namespace Illuminate\View\Engines;

use Illuminate\Contracts\View\Engine;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\ComponentSlot;
use Illuminate\View\Factory;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Exception\CommonMarkException;
use League\Config\Exception\ConfigurationExceptionInterface;

class MarkdownEngine implements Engine
{
    /**
     * The view to use for markdown layouts.
     *
     * @var string
     */
    public $layoutName = null;

    /**
     * The variable to pass rendered markdown to the layout as.
     *
     * @var string
     */
    public $slotName = 'slot';

    /**
     * Create a new markdown engine instance.
     *
     * @param  Filesystem  $files
     * @param  ConverterInterface  $converter
     */
    public function __construct(
        public Filesystem $files,
        public ConverterInterface $converter,
        public Factory $view,
    ) {
    }

    /**
     * Set the layout to render markdown in.
     *
     * @param  string  $view
     * @param  string|null  $slot
     * @return void
     */
    public function setLayout($view, $slot = 'slot')
    {
        $this->layoutName = $view;
        $this->slotName = $slot;
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
        $slot = (string) $this->converter->convert($this->files->get($path));

        if (! $this->layoutName) {
            return $slot;
        }

        return $this->view->make($this->layoutName, [
            ...$data,
            $this->slotName => new ComponentSlot($slot),
        ]);
    }
}
