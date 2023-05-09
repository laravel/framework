<?php

namespace Illuminate\Translation;

use RuntimeException;

class MissingTranslationViolationException extends RuntimeException
{
    /**
     * The key of the missing translation.
     *
     * @var string
     */
    public $key;

    /**
     * Create a new exception instance.
     *
     * @param  string  $key
     * @return static
     */
    public function __construct($key)
    {
        parent::__construct("Attempted to retrieve missing translation [{$key}].");

        $this->key = $key;
    }
}
