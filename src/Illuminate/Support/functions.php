<?php

namespace Illuminate\Support;

use Symfony\Component\Process\ExecutableFinder;
use Illuminate\Support\Process\PhpExecutableFinder;

/**
 * Determine the PHP Binary.
 *
 * @return string
 */
function php_binary()
{
    return (new PhpExecutableFinder)->find(false) ?: 'php';
}
