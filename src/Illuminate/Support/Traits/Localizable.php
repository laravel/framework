<?php

namespace Illuminate\Support\Traits;

use Illuminate\Container\Container;

trait Localizable
{
    /**
     * Run the callback with the given locale.
     *
     * @template TReturn
     *
     * @param  string  $locale
     * @param  (\Closure(): TReturn)  $callback
     * @return TReturn
     */
    public function withLocale($locale, $callback)
    {
        if (! $locale) {
            return $callback();
        }

        $app = Container::getInstance();

        $original = $app->getLocale();

        try {
            $app->setLocale($locale);

            return $callback();
        } finally {
            $app->setLocale($original);
        }
    }
}
