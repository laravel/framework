<?php

namespace Illuminate\Foundation\Events;

class LocaleUpdated
{
    /**
     * The new locale.
     *
     * @var string
     */
    public $locale;

    /**
     * The previous locale.
     *
     * @var ?string
     */
    public $previous;

    /**
     * Create a new event instance.
     *
     * @param  string  $locale
     */
    public function __construct($locale, $previous = null)
    {
        $this->locale = $locale;

        $this->previous = $previous;
    }
}
