<?php

namespace Illuminate\Support\Facades;

/**
 * @method static bool requirePackages(array<int, string> $packages, bool $dev = false, \Closure|\Symfony\Component\Console\Output\OutputInterface|null $output = null, string|null $composerBinary = null)
 * @method static bool removePackages(array<int, string> $packages, bool $dev = false, \Closure|\Symfony\Component\Console\Output\OutputInterface|null $output = null, string|null $composerBinary = null)
 * @method static void modify(callable(array<string, mixed>):array<string, mixed> $callback)
 * @method static int dumpAutoloads(string|array<int, string> $extra = '', string|null $composerBinary = null)
 * @method static int dumpOptimized(string|null $composerBinary = null)
 * @method static array<int, string> findComposer(string|null $composerBinary = null)
 * @method static \Illuminate\Support\Composer setWorkingPath(string $path)
 * @method static string|null getVersion()
 * @method static array<string, mixed> getConfig()
 * @method static array<string, string> getNamespaces()
 *
 * @see \Illuminate\Support\Composer
 */
class Composer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'composer';
    }
}
