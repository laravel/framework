<?php

namespace Illuminate\Support\NodePackageManagers;

use Illuminate\Support\Contracts\NodePackageManager;

class Pnpm implements NodePackageManager
{
    /**
     * Determine if the pnpm package manager is in use.
     *
     * @return bool
     */
    public static function matches(): bool
    {
        return file_exists(getcwd().'/pnpm-lock.yaml');
    }

    /**
     * Get the command to run a script using pnpm.
     *
     * @param  string  $command
     * @return string
     */
    public function getRunCommand(string $command): string
    {
        return "pnpm run {$command}";
    }

    /**
     * Get the command to execute a package using pnpm.
     *
     * @param  string  $command
     * @return string
     */
    public function getExecCommand(string $command): string
    {
        return "pnpm dlx {$command}";
    }
}
