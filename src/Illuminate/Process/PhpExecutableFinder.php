<?php

namespace Illuminate\Process;

use Symfony\Component\Process\PhpExecutableFinder as SymfonyPhpExecutableFinder;

class PhpExecutableFinder extends SymfonyPhpExecutableFinder
{
    private ExecutableFinderDecorator $executableFinder;

    public function __construct()
    {
        $this->executableFinder = new ExecutableFinderDecorator();
    }
}
