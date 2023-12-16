<?php

namespace Illuminate\View\Engines;

use Illuminate\Contracts\View\Engine;
use Illuminate\Filesystem\Filesystem;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Exception\CommonMarkException;
use League\Config\Exception\ConfigurationExceptionInterface;

class MarkdownEngine implements Engine
{

    /**
     * Create a new markdown engine instance.
     *
     * @param  Filesystem  $files
     * @param  ConverterInterface  $converter
     */
    public function __construct(
        public Filesystem $files,
        public ConverterInterface $converter
    ) {
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
        return (string) $this->converter->convert($this->files->get($path));
    }
}
