<?php

namespace Illuminate\Support\Facades;

/**
 * @method static void addJsonPath(string $path)
 * @method static void addLines(array $lines, string $locale, string $namespace = '*')
 * @method static void addNamespace(string $namespace, string $hint)
 * @method static string choice(string $key, \Countable|int|array $number, array $replace = [], string|null $locale = null)
 * @method static void determineLocalesUsing(callable $callback)
 * @method static void flushMacros()
 * @method static void flushParsedKeys()
 * @method static string|array get(string $key, array $replace = [], string|null $locale = null, bool $fallback = true)
 * @method static string getFallback()
 * @method static \Illuminate\Contracts\Translation\Loader getLoader()
 * @method static string getLocale()
 * @method static \Illuminate\Translation\MessageSelector getSelector()
 * @method static bool has(string $key, string|null $locale = null, bool $fallback = true)
 * @method static bool hasForLocale(string $key, string|null $locale = null)
 * @method static bool hasMacro(string $name)
 * @method static void load(string $namespace, string $group, string $locale)
 * @method static string locale()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static array parseKey(string $key)
 * @method static void setFallback(string $fallback)
 * @method static void setLoaded(array $loaded)
 * @method static void setLocale(string $locale)
 * @method static void setParsedKey(string $key, array $parsed)
 * @method static void setSelector(\Illuminate\Translation\MessageSelector $selector)
 *
 * @see \Illuminate\Translation\Translator
 */
class Lang extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'translator';
    }
}
