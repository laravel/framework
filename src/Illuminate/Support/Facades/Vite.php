<?php

namespace Illuminate\Support\Facades;

use Illuminate\Foundation\Vite as Factory;

/**
 * @method static string useCspNonce(?string $nonce = null)
 * @method static string|null cspNonce()
 * @method static string asset(string $asset, string|null $buildDirectory)
 * @method static string toHtml()
 * @method static \Illuminate\Foundation\Vite useIntegrityKey(string|false $key)
 * @method static \Illuminate\Foundation\Vite useScriptTagAttributes(callable|array $callback)
 * @method static \Illuminate\Foundation\Vite useStyleTagAttributes(callable|array $callback)
 * @method static \Illuminte\Foundation\Vite withEntryPoints(array $entryPoints)
 * @method static \Illuminte\Foundation\Vite useHotFile(string $path)
 * @method static \Illuminte\Foundation\Vite useBuildDirectory(string $path)
 *
 * @see \Illuminate\Foundation\Vite
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
        return Factory::class;
    }
}
