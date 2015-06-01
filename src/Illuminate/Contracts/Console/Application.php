<?php

namespace Illuminate\Contracts\Console;

interface Application
{
    /**
     * Call a console application command.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return int
     */
    public function call($command, array $parameters = []);

    /**
     * Get the output from the last command.
     *
     * @return string
     */
    public function output();
}
