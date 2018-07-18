<?php

namespace Illuminate\Translation\Events;

class TranslationNotFound
{
    /**
     * The key of the missing translation
     *
     * @var string
     */
    public $key;

    /**
     * The locale in which the translation was searched
     *
     * @var string
     */
    public $locale;

    /**
     * The array of replacements passed to the translator
     *
     * @var array
     */
    public $replacements;

    /**
     * Creates the event.
     *
     * @param string $key
     * @param string $locale
     * @param array $replacements
     */
    public function __construct(string $key, string $locale, array $replacements = [])
    {
        $this->key = $key;
        $this->locale = $locale;
        $this->replacements = $replacements;
    }
}
