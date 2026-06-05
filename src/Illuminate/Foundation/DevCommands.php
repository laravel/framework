<?php

namespace Illuminate\Foundation;

use Exception;
use Illuminate\Support\NodePackageManager;
use ReflectionClass;

class DevCommands
{
    /**
     * Counter to keep track of how many colors have been assigned,
     * used to ensure colors are reused only after all have been used at least once.
     *
     * @var int
     */
    protected static $colorCount = 0;

    /**
     * Predefined colors to assign to commands when displayed in the console.
     *
     * @var array<int, string>
     */
    protected static $colors = [
        DevCommand::BLUE,
        DevCommand::PURPLE,
        DevCommand::PINK,
        DevCommand::ORANGE,
        DevCommand::GREEN,
        DevCommand::YELLOW,
    ];

    /**
     * The resolved NodePackageManager instance.
     *
     * @var NodePackageManager|null
     */
    protected static ?NodePackageManager $packageManager = null;

    /**
     * The registered development commands.
     *
     * @var array
     */
    protected static $commands = [];

    /**
     * The names of commands that should be excluded when running the "dev" command.
     *
     * @var array
     */
    protected static $except = [];

    /**
     * The names of commands that should be included when running the "dev" command.
     *
     * @var array
     */
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
     * @param  string  $command
     * @param  string|null  $name
     * @return DevCommand
     */
    public static function register(string $command, ?string $name = null): DevCommand
    {
        if (! app()->runningInConsole()) {
            // If we're not running in the console, just return a dummy DevCommand instance.
            return new DevCommand('', '');
        }

        self::preventVendorRegistration($name ?? $command);

        $devCommand = new DevCommand($command, $name);

        self::$commands[$devCommand->name()] = $devCommand;

        return $devCommand;
    }

    /**
     * Registers an Artisan command, automatically prefixing it with "php artisan".
     *
     * @param  string  $command
     * @param  string|null  $name
     * @return DevCommand
     */
    public static function artisan(string $command, ?string $name = null): DevCommand
    {
        return self::register("php artisan {$command}", $name ?? self::nameFromCommand($command));
    }

    /**
     * Registers a Node command, automatically prefixing it with the detected package manager's run command.
     *
     * @param  string  $command
     * @param  string|null  $name
     * @return DevCommand
     */
    public static function node(string $command, ?string $name = null): DevCommand
    {
        return self::register(self::getPackageManager()->getRunCommand($command), $name ?? self::nameFromCommand($command));
    }

    /**
     * Registers a Node command, automatically prefixing it with the detected package manager's exec command.
     *
     * @param  string  $command
     * @param  string|null  $name
     * @return DevCommand
     */
    public static function nodeExec(string $command, ?string $name = null): DevCommand
    {
        return self::register(self::getPackageManager()->getExecCommand($command), $name ?? self::nameFromCommand($command));
    }

    /**
     * Set the commands that should be excluded when running the "dev" command.
     *
     * @param  string  ...$names
     * @return void
     */
    public static function except(...$names): void
    {
        self::$except = $names;
    }

    /**
     * Set the commands that should be included when running the "dev" command.
     *
     * @param  string  ...$names
     * @return void
     */
    public static function only(...$names): void
    {
        self::$only = $names;
    }

    /**
     * Get the registered development commands.
     *
     * @return array
     */
    public static function commands(): array
    {
        $commands = [];

        foreach (self::$commands as $command) {
            $cmd = $command->toArray();

            if ((! empty(self::$only) && ! in_array($cmd['name'], self::$only)) || in_array($cmd['name'], self::$except)) {
                continue;
            }

            $commands[] = $cmd;
        }

        $commands = self::fillInEmptyColors($commands);

        return $commands;
    }

    /**
     * Clear all registered development commands and reset the state of the DevCommands class,
     * including registered commands, exceptions, inclusions, and color assignments.
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
     * Resolve and return the NodePackageManager instance.
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
     * @param  string  $name
     * @return void
     *
     * @throws Exception
     */
    protected static function preventVendorRegistration(string $name)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($trace as $frame) {
            $file = $frame['file'] ?? null;
            $class = $frame['class'] ?? null;

            if ($class === self::class) {
                continue;
            }

            if (! $file && $class) {
                $file = (new ReflectionClass($class))->getFileName();
            }

            if ($file === base_path('artisan')) {
                continue;
            }

            if (! $file) {
                continue;
            }

            if (! str_contains($file, base_path('vendor'))) {
                // We found at least one frame that came from userland code, we're good.
                return;
            }
        }

        throw new Exception(
            "DevCommands should be registered in application code, not within vendor packages. Attempted to register command: {$name}"
        );
    }

    /**
     * Derive a command name from the given command string by taking the first word.
     *
     * @param  string  $command
     * @return string
     */
    protected static function nameFromCommand(string $command): string
    {
        return collect(explode(' ', $command))->first();
    }

    /**
     * Fill in any empty colors in the given commands array, ensuring each command has a color assigned.
     *
     * @param  array  $commands
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
     * @param  array  $commands
     * @return string
     */
    protected static function getColor(array $commands): string
    {
        $existing = array_values(array_filter(array_column($commands, 'color')));
        $available = array_values(array_diff(self::$colors, $existing));

        return $available[0] ?? self::$colors[self::$colorCount++ % count(self::$colors)];
    }
}
