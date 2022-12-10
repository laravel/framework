<?php

namespace Illuminate\Support\Facades;

/**
 * @method static string getDefaultDriver()
 * @method static \Illuminate\Foundation\ViteManager useAppFactory(callable(string, \Illuminate\Contracts\Foundation\Vite, array, \Illuminate\Contracts\Container\Container): \Illuminate\Contracts\Foundation\Vite $appFactory)
 * @method static \Illuminate\Contracts\Foundation\Vite app(string|null $app = null)
 * @method static \Illuminate\Contracts\Foundation\Vite configure(array $config)
 * @method static array preloadedAssets()
 * @method static string|null cspNonce()
 * @method static string useCspNonce(string|null $nonce = null)
 * @method static \Illuminate\Contracts\Foundation\Vite useIntegrityKey(string|false $key)
 * @method static \Illuminate\Contracts\Foundation\Vite withEntryPoints(array $entryPoints)
 * @method static \Illuminate\Contracts\Foundation\Vite useManifestFilename(string $filename)
 * @method static string hotFile()
 * @method static \Illuminate\Contracts\Foundation\Vite useHotFile(string $path)
 * @method static \Illuminate\Contracts\Foundation\Vite useBuildDirectory(string $path)
 * @method static \Illuminate\Contracts\Foundation\Vite useScriptTagAttributes((callable(string, string, ?array, ?array): array)|array $attributes)
 * @method static \Illuminate\Contracts\Foundation\Vite useStyleTagAttributes((callable(string, string, ?array, ?array): array)|array $attributes)
 * @method static \Illuminate\Contracts\Foundation\Vite usePreloadTagAttributes((callable(string, string, ?array, ?array): array)|array $attributes)
 * @method static \Illuminate\Support\HtmlString|void reactRefresh()
 * @method static string asset(string $asset, string|null $buildDirectory = null)
 * @method static string|null manifestHash(void $buildDirectory = null)
 * @method static bool isRunningHot()
 * @method static string toHtml()
 * @method static mixed driver(string|null $driver = null)
 * @method static \Illuminate\Foundation\ViteManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static \Illuminate\Contracts\Container\Container getContainer()
 * @method static \Illuminate\Foundation\ViteManager setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static \Illuminate\Foundation\ViteManager forgetDrivers()
 *
 * @see \Illuminate\Foundation\ViteManager
 */
class Vite extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'vite';
    }
}
