<?php

namespace Illuminate\Translation\Events;

class KeyNotTranslated
{
    public $key;

    public function __construct($key)
    {
        $this->key = $key;
    }
}
