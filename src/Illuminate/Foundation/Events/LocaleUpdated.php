<?php

namespace Illuminate\Foundation\Events;

class LocaleUpdated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $locale,
        public ?string $previousLocale = null,
    ) {
    }
}
