<?php

namespace Illuminate\Contracts\Routing;

/**
 * Interface LocaleManager
 *
 * @package Illuminate\Contracts\Routing
 */
interface LocaleManager
{
    /**
     * Get a list of common languages.
     *
     * @return array
     */
    public function getLanguages();

    /**
     * Add language to common languages list.
     *
     * @param  string  $language
     */
    public function addLanguages($language);

    /**
     * Replace common languages list.
     *
     * @param  array  $languages
     */
    public function setLanguages(array $languages);

    /**
     * Get active application language.
     *
     * @return string
     */
    public function getActiveLocale();
}
