<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Contracts\Console\Kernel;

trait InteractsWithConsole
{
    /**
     * Call artisan command and return code.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return int
     */
    public function artisan($command, $parameters = [])
    {
        return $this->app[Kernel::class]->call($command, $parameters);
    }
}
