<?php

namespace Illuminate\Support\NodePackageManagers;

use Illuminate\Support\Contracts\NodePackageManager;

class Yarn implements NodePackageManager
{
    /**
     * Determine if the yarn package manager is in use.
     *
     * @return bool
     */
    public static function isInUse(): bool
    {
        return file_exists(getcwd() . '/yarn.lock');
    }

    /* Get the command to run a script using yarn.
     *
     * @param string $command
     * @return string
     */
    public function getRunCommand(string $command): string
    {
        return "yarn run {$command}";
    }

    /**
     * Get the command to execute a package using yarn.
     *
     * @param string $command
     * @return string
     */
    public function getExecCommand(string $command): string
    {
        return "yarn dlx {$command}";
    }
}
