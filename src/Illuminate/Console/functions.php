<?php

namespace Illuminate\Console;

use Exception;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

if (! function_exists('Illuminate\Console\checked_var_export')) {
    /**
     * Returns a parsable string representation of a variable.
     *
     * @param  mixed  $value
     * @return string
     */
    function checked_var_export($value)
    {
        $exported = var_export($value, true);

        $process = new Process([
            (new PhpExecutableFinder())->find(false) ?: 'php',
            '-r',
            "{$exported};",
        ]);

        if ($process->run()) {
            throw new Exception('Unable to export file: Candidate file content cannot be parsed.');
        }

        return $exported;
    }
}
