<?php

namespace Illuminate\Translation;

use RuntimeException;

class MissingTranslationKeyViolationException extends RuntimeException
{
    /**
     * The name of the affected Eloquent model.
     *
     * @var string
     */
    public $key;

    /**
     * Create a new exception instance.
     *
     * @param  object  $model
     * @param  string  $relation
     * @return static
     */
    public function __construct($key)
    {
        parent::__construct("Attempted to translate [{$key}] but prevention for missing translation key is enabled.");

        $this->key = $key;
    }
}
