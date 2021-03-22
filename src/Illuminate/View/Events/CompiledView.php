<?php

namespace Illuminate\View\Events;

class CompiledView
{
    /**
     * Path to the compiled version of a view.
     *
     * @var string
     */
    public $path;

    /**
     * Contents of the compiled view.
     *
     * @var string
     */
    public $contents;

    /**
     * Create a new event instance.
     *
     * @param  string  $path
     * @param  string  $contents
     * @return void
     */
    public function __construct($path, $contents)
    {
        $this->path = $path;
        $this->contents = $contents;
    }
}
