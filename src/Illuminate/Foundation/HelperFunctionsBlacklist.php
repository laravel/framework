<?php

namespace Illuminate\Foundation;

use ReflectionClass;
use Composer\Autoload\ClassLoader;

/**
 * Determine which global helper functions should be disabled, based on
 * the 'disabled-functions' key in the project's composer.json file.
 */
class HelperFunctionsBlacklist
{
    /**
     * List of function names to be disabled, or `true` to disable all functions.
     *
     * @var bool|string[]
     */
    private static $disabledFunctions;

    /**
     * Whether or not the configuration has already been loaded before.
     *
     * @var bool
     */
    private static $configurationHasBeenLoaded = false;

    /**
     * Read the composer.json file and parse its contents
     * to find the list of functions to disable.
     *
     * @param string $composerJsonPath
     */
    public static function loadConfiguration($composerJsonPath = null)
    {
        // Let's start fresh, in case the configuration has been loaded before
        static::$disabledFunctions = null;

        static::$configurationHasBeenLoaded = true;

        if ($composerJsonPath === null) {
            $composerJsonPath = static::getComposerJsonPath();
        }

        $composerJson = json_decode(file_get_contents($composerJsonPath));

        if (! isset(
            $composerJson->extra,
            $composerJson->extra->laravel,
            $composerJson->extra->laravel->{'disabled-functions'}
        )) {
            return;
        }

        $disabledFunctions = $composerJson->extra->laravel->{'disabled-functions'};
        static::registerBlacklist($disabledFunctions);
    }

    /**
     * Parse and register the blacklisted items to the static instance.
     *
     * @param array|bool $disabledFunctions
     * @return void
     */
    private static function registerBlacklist($disabledFunctions)
    {
        if (is_bool($disabledFunctions)) {
            static::$disabledFunctions = $disabledFunctions;

            return;
        }

        if (is_array($disabledFunctions)) {
            foreach ($disabledFunctions as $functionName) {
                if (! is_string($functionName)) {
                    throw new \Exception("Invalid config type for 'disabled-functions' with value '{$functionName}' in composer.json - must be a string.");
                }

                static::$disabledFunctions[] = $functionName;
            }

            return;
        }

        throw new \Exception("Invalid config type for 'disabled-functions' in composer.json - must be either a boolean or array of strings.");
    }

    /**
     * Check if a function is enabled and allowed to be registered.
     *
     * @param string $functionName
     *
     * @return bool
     */
    public static function isEnabled($functionName)
    {
        if (! static::$configurationHasBeenLoaded) {
            static::loadConfiguration();
        }

        if (is_bool(static::$disabledFunctions)) {
            return ! static::$disabledFunctions;
        }

        if (gettype(static::$disabledFunctions) === 'array') {
            return ! in_array($functionName, static::$disabledFunctions);
        }

        return true;
    }

    /**
     * Get the path to the main application's composer.json - this is needed
     * as Laravel::appPath() is not available as the application has not
     * yet been fully booted at the point this class is being used.
     *
     * @return string
     */
    private static function getComposerJsonPath()
    {
        $reflection = new ReflectionClass(ClassLoader::class);
        $vendorDirectory = dirname(dirname($reflection->getFileName()));
        $mainApplicationDirectory = dirname($vendorDirectory);

        return $mainApplicationDirectory . '/composer.json';
    }
}
