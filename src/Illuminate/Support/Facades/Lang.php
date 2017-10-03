<?php

namespace Illuminate\Support\Facades;

/**
 * @method static bool hasForLocale(string $key, string | null $locale) Determine if a translation exists for a given locale.
 * @method static bool has(string $key, string | null $locale, bool $fallback) Determine if a translation exists.
 * @method static string|array|null trans(string $key, array $replace, string $locale) Get the translation for a given key.
 * @method static string|array|null get(string $key, array $replace, string | null $locale, bool $fallback) Get the translation for the given key.
 * @method static string getFromJson(string $key, array $replace, string $locale) Get the translation for a given key from the JSON translation files.
 * @method static string transChoice(string $key, int | array | \Countable $number, array $replace, string $locale) Get a translation according to an integer value.
 * @method static string choice(string $key, int | array | \Countable $number, array $replace, string $locale) Get a translation according to an integer value.
 * @method static void addLines(array $lines, string $locale, string $namespace) Add translation lines to the given locale.
 * @method static void load(string $namespace, string $group, string $locale) Load the specified language group.
 * @method static void addNamespace(string $namespace, string $hint) Add a new namespace to the loader.
 * @method static void addJsonPath(string $path) Add a new JSON path to the loader.
 * @method static array parseKey(string $key) Parse a key into namespace, group, and item.
 * @method static \Illuminate\Translation\MessageSelector getSelector() Get the message selector instance.
 * @method static void setSelector(\Illuminate\Translation\MessageSelector $selector) Set the message selector instance.
 * @method static \Illuminate\Contracts\Translation\Loader getLoader() Get the language line loader implementation.
 * @method static string locale() Get the default locale being used.
 * @method static string getLocale() Get the default locale being used.
 * @method static void setLocale(string $locale) Set the default locale.
 * @method static string getFallback() Get the fallback locale being used.
 * @method static void setFallback(string $fallback) Set the fallback locale being used.
 * @method static void setParsedKey(string $key, array $parsed) Set the parsed value of a key.
 * @method static void macro(string $name, object | callable $macro) Register a custom macro.
 * @method static void mixin(object $mixin) Mix another object into the class.
 * @method static bool hasMacro(string $name) Checks if macro is registered.
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
