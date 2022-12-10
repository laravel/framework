<?php

namespace Illuminate\Support\Facades;

use Illuminate\Contracts\Foundation\Vite as ViteContract;

/**
 * @method static \Illuminate\Foundation\ViteManager useAppFactory(callable(string, ViteContract, array, Container): ViteContract $appFactory)
 * @method static ViteContract app(string|null $app = null)
 * @method static ViteContract configure(array $config)
 * @method static array preloadedAssets()
 * @method static string|null cspNonce()
 * @method static string useCspNonce(string|null $nonce = null)
 * @method static ViteContract useIntegrityKey(string|false $key)
 * @method static ViteContract withEntryPoints(array $entryPoints)
 * @method static ViteContract useManifestFilename(string $filename)
 * @method static string hotFile()
 * @method static ViteContract useHotFile(string $path)
 * @method static ViteContract useBuildDirectory(string $path)
 * @method static ViteContract useScriptTagAttributes((callable(string, string, ?array, ?array): array)|array $attributes)
 * @method static ViteContract useStyleTagAttributes((callable(string, string, ?array, ?array): array)|array $attributes)
 * @method static ViteContract usePreloadTagAttributes((callable(string, string, ?array, ?array): array)|array $attributes)
 * @method static \Illuminate\Support\HtmlString|void reactRefresh()
 * @method static string asset(string $asset, string|null $buildDirectory = null)
 * @method static string|null manifestHash(string|null $buildDirectory = null)
 * @method static bool isRunningHot()
 * @method static string toHtml()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
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
