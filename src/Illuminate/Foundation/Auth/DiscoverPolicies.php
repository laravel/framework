<?php

namespace Illuminate\Foundation\Auth;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class DiscoverPolicies
{
    /**
     * The callback to be used to guess class names.
     *
     * @var (callable(SplFileInfo, string): class-string)|null
     */
    public static $guessClassNamesUsingCallback = null;

    /**
     * Get all of the classes and policies by searching the given class directory.
     *
     * @param  array<int, string>|string  $classPaths
     * @return array<class-string, class-string>
     */
    public static function within(array|string $classPaths, string $basePath): array
    {
        if (Arr::wrap($classPaths) === []) {
            return [];
        }

        return static::getClassPolicies(
            Finder::create()->files()->in($classPaths), $basePath
        );
    }

    /**
     * Get all of the classes and their corresponding policies.
     *
     * @param  iterable<SplFileInfo>  $files
     * @return array<class-string, class-string>
     */
    protected static function getClassPolicies(iterable $files, string $basePath): array
    {
        $policies = [];

        foreach ($files as $file) {
            $class = static::classFromFile($file, $basePath);
            $policy = Gate::getPolicyClassFor($class);

            if ($policy !== null) {
                $policies[$class] = $policy;
            }
        }

        return $policies;
    }

    /**
     * Extract the class name from the given file path.
     *
     * @return class-string<*>
     */
    protected static function classFromFile(SplFileInfo $file, string $basePath): string
    {
        if (static::$guessClassNamesUsingCallback) {
            return call_user_func(static::$guessClassNamesUsingCallback, $file, $basePath);
        }

        $class = trim(Str::replaceFirst($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        return ucfirst(Str::camel(str_replace(
            [DIRECTORY_SEPARATOR, ucfirst(basename(app()->path())).'\\'],
            ['\\', app()->getNamespace()],
            ucfirst(Str::replaceLast('.php', '', $class))
        )));
    }

    /**
     * Specify a callback to be used to guess class names.
     *
     * @param  callable(SplFileInfo, string): class-string<*>  $callback
     */
    public static function guessClassNamesUsing(callable $callback): void
    {
        static::$guessClassNamesUsingCallback = $callback;
    }
}
