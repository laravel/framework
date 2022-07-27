<?php

namespace Illuminate\Support\Facades;

/**
 * @method static string useCspNonce(?string $nonce = null)
 * @method static string|null cspNonce()
 * @method static \Illuminte\Foundation\Vite useIntegrityKey(string|false $key)
 * @method static \Illuminte\Foundation\Vite useAttributesForScriptTag(callable|array $callback)
 * @method static \Illuminte\Foundation\Vite useAttributesForStylesheetTag(callable|array $callback)
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
        return \Illuminate\Foundation\Vite::class;
    }
}
