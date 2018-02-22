<?php

namespace Illuminate\Support\Traits;

trait Localizable
{
    /**
     * Run the callback with the given locale.
     *
     * @param  string  $locale
     * @param  \Illuminate\Contracts\Translation\Translator  $translator
     * @param  \Closure  $callback
     * @return bool
     */
    public function withLocale($locale, $translator, $callback)
    {
        if (! $locale || ! $translator) {
            return $callback();
        }

        $original = $translator->getLocale();

        try {
            $translator->setLocale($locale);

            return $callback();
        } finally {
            $translator->setLocale($original);
        }
    }
}
