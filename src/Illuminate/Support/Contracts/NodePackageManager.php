<?php

namespace Illuminate\Support\Contracts;

interface NodePackageManager
{
    /**
     * Determine if the package manager is in use.
     *
     * @return bool
     */
    public static function matches(): bool;

    /**
     * Get the command to run a script using the package manager.
     *
     * @param  string  $command
     * @return string
     */
    public function getRunCommand(string $command): string;

    /**
     * Get the command to execute a package using the package manager.
     *
     * @param  string  $command
     * @return string
     */
    public function getExecCommand(string $command): string;
}
