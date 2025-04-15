<?php

namespace Illuminate\Foundation\Events;

class LocaleUpdated
{
    /**
     * Create a new event instance.
     *
     * @param  string  $locale  The new locale.
     */
    public function __construct(
        public string $locale
    ) {
    }
}
