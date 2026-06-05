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

    protected static $except = [];

    protected static $only = [];

    /**
     * Register the default development commands.
     *
     * @return void
     */
    public static function registerDefaults()
    {
        self::artisan('serve --host=localhost', 'server');
        self::artisan('queue:listen --tries=1 --timeout=0', 'queue');
        self::artisan('pail --timeout=0', 'logs');
        self::node('dev', 'vite');
    }

    /**
     * Register a development command.
     *
     * @param string $command
     * @param null|string $name
     * @return DevCommand
     */
    public static function register(string $command, ?string $name = null): DevCommand
    {
        // $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        // self::preventVendorRegistration($name, $trace);

        $devCommand = new DevCommand($command, $name);

        self::$commands[$name] = $devCommand;

        return $devCommand;
    }

    /**
     * Registers an Artisan command, automatically prefixing it with "php artisan".
     *
     * @param string $command
     * @param null|string $name
     * @return DevCommand
     */
    public static function artisan(string $command, ?string $name = null): DevCommand
    {
        return self::register("php artisan {$command}", $name ?? self::nameFromCommand($command));
    }

    /**
     * Registers a Node command, automatically prefixing it with the detected package manager's run command.
     *
     * @param string $command
     * @param null|string $name
     * @return DevCommand
     */
    public static function node(string $command, ?string $name = null): DevCommand
    {
        return self::register(self::getPackageManager()->getRunCommand($command), $name ?? self::nameFromCommand($command));
    }

    /**
     * Registers a Node command, automatically prefixing it with the detected package manager's exec command.
     *
     * @param string $command
     * @param null|string $name
     * @return DevCommand
     */
    public static function nodeExec(string $command, ?string $name = null): DevCommand
    {
        return self::register(self::getPackageManager()->getExecCommand($command), $name ?? self::nameFromCommand($command));
    }

    /**
     * Set the commands that should be included when running the "dev" command, excluding any commands not in the given list.
     *
     * @param mixed ...$commands
     * @return void
     */
    public static function except(...$commands): void
    {
        self::$except = $commands;
    }

    /**
     * Set the commands that should be included when running the "dev" command, excluding any commands not in the given list.
     *
     * @param mixed ...$commands
     * @return void
     */
    public static function only(...$commands): void
    {
        self::$only = $commands;
    }

    /**
     * Get the registered development commands.
     *
     * @return array
     */
    public static function getCommands(): array
    {
        $commands = [];

        foreach (self::$commands as $command) {
            $cmd = $command->toArray();

            if ((!empty(self::$only) && !in_array($cmd['name'], self::$only)) || in_array($cmd['name'], self::$except)) {
                continue;
            }

            $commands[] = $cmd;
        }

        $commands = self::fillInEmptyColors($commands);

        return $commands;
    }

    /**
     * Clear all registered development commands and reset the state of the DevCommands class, including registered commands, exceptions, inclusions, and color assignments.
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$commands = [];
        self::$except = [];
        self::$only = [];
        self::$colorCount = 0;
    }

    /**
     * Create a new DevCommands instance.
     *
     * @return NodePackageManager
     */
    protected static function getPackageManager(): NodePackageManager
    {
        return self::$packageManager ??= new NodePackageManager();
    }

    /**
     * Prevent automatic registration of DevCommands from within vendor packages.
     *
     * @param string $name
     * @param array $trace
     * @return void
     * @throws Exception
     */
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
     * Derive a command name from the given command string by taking the first word.
     *
     * @param string $command
     * @return string
     */
    protected static function nameFromCommand(string $command): string
    {
        return collect(explode(' ', $command))->first();
    }

    /**
     * Fill in any empty colors in the given commands array, ensuring each command has a color assigned.
     *
     * @param array $commands
     * @return array
     */
    protected static function fillInEmptyColors(array $commands): array
    {
        foreach ($commands as &$command) {
            if (empty($command['color'])) {
                $command['color'] = self::getColor($commands);
            }
        }

        return $commands;
    }

    /**
     * Get a color for a command, ensuring that colors are reused only after all available colors have been used at least once.
     *
     * @param array $commands
     * @return string
     */
    protected static function getColor(array $commands): string
    {
        $existing = array_values(array_filter(array_column($commands, 'color')));
        $available = array_values(array_diff(self::$colors, $existing));

        return $available[0] ?? self::$colors[self::$colorCount++ % count(self::$colors)];
    }
}
