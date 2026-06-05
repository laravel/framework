<?php

namespace Illuminate\Foundation;

use Exception;
use Illuminate\Support\NodePackageManager;
use ReflectionClass;

class DevCommands
{
    protected static $colorCount = 0;

    protected static $colors = [
        DevCommand::BLUE,
        DevCommand::PURPLE,
        DevCommand::PINK,
        DevCommand::ORANGE,
        DevCommand::GREEN,
        DevCommand::YELLOW,
    ];

    protected static ?NodePackageManager $packageManager = null;

    protected static $commands = [];

    public static function register(string $command, ?string $name = null): DevCommand
    {
        // $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        // self::preventVendorRegistration($name, $trace);

        $devCommand = new DevCommand($command, $name);

        self::$commands[$name] = $devCommand;

        return $devCommand;
    }

    protected static function preventVendorRegistration(string $name, array $trace)
    {
        $caller = $trace[1];

        if ($caller['class']) {
            $reflection = new ReflectionClass($caller['class']);

            if (str_contains($reflection->getFileName(), 'vendor')) {
                throw new Exception("DevCommands should be registered in application code, not within vendor packages. Attempted to register command: {$name}");
            }
        } else if (str_contains($caller['file'], 'vendor')) {
            throw new Exception("DevCommands should be registered in application code, not within vendor packages. Attempted to register command: {$name}");
        }
    }

    /**
     * Registers an Artisan command, automatically prefixing it with "php artisan".
     */
    public static function artisan(string $command, ?string $name = null): DevCommand
    {
        return self::register("php artisan {$command}", $name ?? self::nameFromCommand($command));
    }

    /**
     * Registers a JavaScript command, automatically prefixing it with the detected package manager's run command.
     */
    public static function node(string $command, ?string $name = null): DevCommand
    {
        return self::register(self::getPackageManager()->getRunCommand($command), $name ?? self::nameFromCommand($command));
    }

    /**
     * Registers a JavaScript command with the full command provided, bypassing the package manager prefix.
     */
    public static function nodeExec(string $command, ?string $name = null): DevCommand
    {
        return self::register(self::getPackageManager()->getExecCommand($command), $name ?? self::nameFromCommand($command));
    }

    protected static function getPackageManager(): NodePackageManager
    {
        return self::$packageManager ??= new NodePackageManager();
    }

    public static function getCommands(): array
    {
        $commands = [];

        foreach (self::$commands as $command) {
            $cmd = $command->toArray();
            $commands[] = $cmd;
        }

        $commands = self::fillInEmptyColors($commands);

        return $commands;
    }

    protected static function nameFromCommand(string $command): string
    {
        return collect(explode(' ', $command))->first();
    }

    protected static function fillInEmptyColors(array $commands): array
    {
        foreach ($commands as &$command) {
            if (empty($command['color'])) {
                $command['color'] = self::getColor($commands);
            }
        }

        return $commands;
    }

    protected static function getColor(array $commands): string
    {
        $existing = array_values(array_filter(array_column($commands, 'color')));
        $available = array_values(array_diff(self::$colors, $existing));

        return $available[0] ?? self::$colors[self::$colorCount++ % count(self::$colors)];
    }
}
