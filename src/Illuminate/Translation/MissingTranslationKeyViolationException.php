<?php

namespace Illuminate\Translation;

use RuntimeException;

class MissingTranslationKeyViolationException extends RuntimeException
{
    /**
     * The Key of the missing translation.
     *
     * @var string
     */
    public $key;

    /**
     * The default locale that was used.
     *
     * @var array
     */
    public $locales;

    /**
     * Create a new exception instance.
     *
     * @param  object  $model
     * @param  string  $relation
     * @return static
     */
    public function __construct($key, $locales)
    {
        $l = implode(',', $locales);
        parent::__construct("Attempted to translate [{$key}] under the locales [{$l}] but prevention for missing translation key is enabled.");

        $this->key = $key;
        $this->locales = $locales;
    }
}
