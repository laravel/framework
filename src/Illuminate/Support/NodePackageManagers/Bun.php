<?php

namespace Illuminate\Support\NodePackageManagers;

use Illuminate\Support\Contracts\NodePackageManager;

class Bun implements NodePackageManager
{
    /**
     * Determine if the Bun package manager is in use.
     *
     * @return bool
     */
    public static function matches(): bool
    {
        foreach (['bun.lock', 'bun.lockb'] as $lockFile) {
            if (file_exists(getcwd().'/'.$lockFile)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the command to run a script using Bun.
     *
     * @param  string  $command
     * @return string
     */
    public function getRunCommand(string $command): string
    {
        return "bun run {$command}";
    }

    /**
     * Get the command to execute a package using Bun.
     *
     * @param  string  $command
     * @return string
     */
    public function getExecCommand(string $command): string
    {
        return "bunx {$command}";
    }
}
