<?php

namespace Illuminate\Contracts\Translation;

interface Translator
{
    /**
     * Get the translation for the given key.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true);

    /**
     * Get the translation for a given key.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string|null  $locale
     * @return string|array
     */
    public function trans($key, array $replace = [], $locale = null);

    /**
     * Get a translation according to an integer value.
     *
     * @param  string  $key
     * @param  int|array|\Countable  $number
     * @param  array   $replace
     * @param  string|null  $locale
     * @return string
     */
    public function transChoice($key, $number, array $replace = [], $locale = null);

    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function getLocale();

    /**
     * Set the default locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale);
}
