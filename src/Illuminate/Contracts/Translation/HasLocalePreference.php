<?php

namespace Illuminate\Contracts\Translation;

interface HasLocalePreference
{
    /**
     * Get the preferred locale of the entity.
     *
     * @return ?string
     */
    public function preferredLocale();
}
