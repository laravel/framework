<?php

namespace Illuminate\Support\Facades;

/**
 * @method static string version()
 * @method static string basePath()
 * @method static string bootstrapPath(string $path = '')
 * @method static string configPath(string $path = '')
 * @method static string databasePath(string $path = '')
 * @method static string environmentPath()
 * @method static string resourcePath(string $path = '')
 * @method static string storagePath()
 * @method static string|bool environment(string|array ...$environments)
 * @method static bool runningInConsole()
 * @method static bool runningUnitTests()
 * @method static bool isDownForMaintenance()
 * @method static void registerConfiguredProviders()
 * @method static \Illuminate\Support\ServiceProvider register(\Illuminate\Support\ServiceProvider|string $provider, bool $force = false)
 * @method static void registerDeferredProvider(string $provider, string $service = null)
 * @method static \Illuminate\Support\ServiceProvider resolveProvider(string $provider)
 * @method static void boot()
 * @method static void booting(callable $callback)
 * @method static void booted(callable $callback)
 * @method static void bootstrapWith(array $bootstrappers)
 * @method static bool configurationIsCached()
 * @method static string detectEnvironment(callable $callback)
 * @method static string environmentFile()
 * @method static string environmentFilePath()
 * @method static string getCachedConfigPath()
 * @method static string getCachedServicesPath()
 * @method static string getCachedPackagesPath()
 * @method static string getCachedRoutesPath()
 * @method static string getLocale()
 * @method static string getNamespace()
 * @method static array getProviders(\Illuminate\Support\ServiceProvider|string $provider)
 * @method static bool hasBeenBootstrapped()
 * @method static void loadDeferredProviders()
 * @method static \Illuminate\Contracts\Foundation\Application loadEnvironmentFrom(string $file)
 * @method static bool routesAreCached()
 * @method static void setLocale(string $locale)
 * @method static bool shouldSkipMiddleware()
 * @method static void terminate()
 *
 * @method static bool bound(string $abstract)
 * @method static void alias(string $abstract, string $alias);
 * @method static void tag(array|string $abstracts, array|mixed $tags);
 * @method static iterable tagged(string $tag);
 * @method static void bind(string $abstract, \Closure|string|null $concrete = null, bool $shared = false);
 * @method static void bindIf($string abstract, \Closure|string|null $concrete = null, bool $shared = false);
 * @method static void singleton(string $abstract, \Closure|string|null$concrete = null);
 * @method static void singletonIf(string $abstract, \Closure|string|null $concrete = null);
 * @method static void extend(string $abstract, Closure $closure);
 * @method static mixed instance(string $abstract, mixed $instance);
 * @method static void addContextualBinding(string $concrete, string $abstract, \Closure|string $implementation);
 * @method static \Illuminate\Contracts\Container\ContextualBindingBuilder when(string|array $concrete);
 * @method \Closure static factory(string $abstract);
 * @method static void flush();
 * @method static mixed make(string $abstract, array $parameters = []);
 * @method static mixed call(callable|string $callback, array $parameters = [], string|null $defaultMethod = null);
 * @method static bool resolved(string $abstract);
 * @method static void resolving(\Closure|string $abstract, Closure|null $callback = null);
 * @method static void afterResolving(\Closure|string $abstract, Closure|null $callback = null);
 *
 * @see \Illuminate\Contracts\Foundation\Application
 */
class App extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'app';
    }
}
