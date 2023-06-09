<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Support\DiscoversClasses;
use Symfony\Component\Finder\Finder;

class DiscoverCommands extends DiscoversClasses
{
    /**
     * Get all of the commands by searching the given commands directory.
     *
     * @param  string  $path
     * @return array
     */
    public static function within($path)
    {
        return static::getCommands(
            (new Finder)->files()->in($path),
        );
    }

    /**
     * Get all of the commands.
     *
     * @param  iterable  $commands
     * @return array
     */
    protected static function getCommands($commands)
    {
        $discoveredCommands = [];

        foreach (self::discoverClasses($commands) as $command) {
            if ($command->isSubclassOf(Command::class) &&
                ! $command->isAbstract()) {
                $discoveredCommands[] = $command->getName();
            }
        }

        return $discoveredCommands;
    }
}
