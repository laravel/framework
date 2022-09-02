<?php

namespace Illuminate\Console\Process;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\Process\Process;

class PendingProcess
{
    use Macroable;

    /**
     * The process's arguments.
     *
     * @var iterable<array-key, string>
     */
    protected $arguments;

    /**
     * The process's path.
     *
     * @var string|null
     */
    protected $path;

    /**
     * Create a new Pending Process instance.
     *
     *
     * @return void
     */
    public function __construct()
    {
        // ..
    }

    /**
     * Sets the process's arguments.
     *
     * @param  iterable<array-key, string>  $arguments
     * @return $this
     */
    public function withArguments($arguments)
    {
        return tap($this, fn () => $this->arguments = array_merge($this->arguments ?? [], array_values($arguments)));
    }

    /**
     * Sets the process's path.
     *
     * @param  string  $path
     * @return $this
     */
    public function path($path)
    {
        return tap($this, fn () => $this->path = $path);
    }

    /**
     * @param  iterable<array-key, string>|string  $arguments
     * @return
     */
    public function run($arguments)
    {
        $this->withArguments(Arr::wrap($arguments));

        return new Response(tap(new Process($this->arguments), function ($process) {
            $process->setWorkingDirectory($this->path ?? getcwd());

            $process->run();
        }));
    }
}
