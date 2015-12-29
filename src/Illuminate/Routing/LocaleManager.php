<?php

namespace Illuminate\Routing;

use Illuminate\Contracts\Routing\LocaleManager as LocaleManagerContract;
use Illuminate\Foundation\Application;

class LocaleManager implements LocaleManagerContract
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Common languages that are merged for routes.
     *
     * @var array
     */
    protected $languages = ['*'];

    /**
     * LocaleManager constructor.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get a list of common languages.
     *
     * @return array
     */
    public function getLanguages()
    {
        return array_unique(array_merge($this->languages, [$this->getActiveLocale()]));
    }

    /**
     * Add language to common languages list.
     *
     * @param  string|array  $language
     */
    public function addLanguages($language)
    {
        if (is_string($language)) {
            $language = func_get_args();
        }

        $this->languages = array_unique(array_merge($this->languages, $language));
    }

    /**
     * replace common languages list.
     *
     * @param  array  $languages
     */
    public function setLanguages(array $languages)
    {
        $this->languages = $languages;
    }

    /**
     * Get active application language.
     *
     * @return string
     */
    public function getActiveLocale()
    {
        return $this->app->getLocale();
    }
}
