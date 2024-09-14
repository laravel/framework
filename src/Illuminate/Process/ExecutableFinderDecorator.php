<?php

namespace Illuminate\Process;

use Symfony\Component\Process\ExecutableFinder;

use function Illuminate\Filesystem\join_paths;

class ExecutableFinderDecorator
{
    /**
     * The Symfony's Executable Finder implementation.
     *
     * @var Symfony\Component\Process\PhpExecutableFinder
     */
    private ExecutableFinder $decorator;

    /**
     * Create a new Executable Finder Decorator.
     *
     * @return void
     */
    public function __construct()
    {
        $this->decorator = new ExecutableFinder();
    }

    /**
     * Replaces default suffixes of executable.
     *
     * @param  array  $suffixes
     * @return void
     */
    public function setSuffixes(array $suffixes): void
    {
        $this->decorator->setSuffixes($suffixes);
    }

    /**
     * Adds new possible suffix to check for executable.
     *
     * @param  string  $suffix
     * @return void
     */
    public function addSuffix(string $suffix): void
    {
        $this->decorator->addSuffix($suffix);
    }

    /**
     * Finds an executable by name.
     *
     * @param  string  $name
     * @param  string|null  $default
     * @param  array  $extraDirs
     * @return string|null
     */
    public function find(string $name, ?string $default = null, array $extraDirs = []): ?string
    {
        if ($herdPath = getenv('HERD_HOME')) {
            $extraDirs[] = join_paths($herdPath, 'bin');
        }

        return $this->decorator->find($name, $default, $extraDirs);
    }
}
