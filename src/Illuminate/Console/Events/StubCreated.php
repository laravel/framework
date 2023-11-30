<?php

namespace Illuminate\Console\Events;

class StubCreated
{
    /**
     * The path to the stub file used.
     *
     * @var string
     */
    public $stubPath;

    /**
     * The path to the file generated from the stub.
     *
     * @var string
     */
    public $outputPath;

    /**
     * Create a new event instance.
     *
     * @param  string  $stubPath
     * @param  string  $outputPath
     */
    public function __construct(string $stubPath, string $outputPath)
    {
        $this->stubPath = $stubPath;
        $this->outputPath = $outputPath;
    }
}
