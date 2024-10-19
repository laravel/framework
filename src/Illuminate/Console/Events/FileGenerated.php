<?php

namespace Illuminate\Console\Events;

class FileGenerated
{
    /**
     * The path of the generated file.
     *
     * @var string
     */
    public $path;

    /**
     * Create a new event instance.
     *
     * @param  string  $path
     * @return void
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }
}
