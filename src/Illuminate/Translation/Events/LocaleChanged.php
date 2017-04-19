<?php

namespace Illuminate\Translation\Events;

class LocaleChanged
{

    /**
     * The changed locale.
     *
     * @var string
     */
    public $locale;

    /**
     * Create a new event instance.
     *
     * @param $locale
     * @return void
     */
    public function __construct($locale)
    {
        $this->locale = $locale;
    }

}