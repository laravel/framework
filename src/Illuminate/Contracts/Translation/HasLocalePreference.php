<?php

namespace Illuminate\Contracts\Translation;

interface HasLocalePreference
{
    /**
     * @return string|null
     */
    public function preferredLocale();
}
