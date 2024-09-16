<?php

namespace Illuminate\Support\Process;

use Symfony\Component\Process\PhpExecutableFinder as SymfonyPhpExecutableFinder;

class PhpExecutableFinder extends SymfonyPhpExecutableFinder
{
    /**
     * The Symfony's Executable Finder Decorator implementation.
     *
     * @var Symfony\Component\Process\PhpExecutableFinder
     */
    private ExecutableFinderDecorator $executableFinder;

    /**
     * Construct a new PHP Executable Finder.
     */
    public function __construct()
    {
        $this->executableFinder = new ExecutableFinderDecorator();
    }
}
