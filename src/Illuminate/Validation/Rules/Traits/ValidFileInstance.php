<?php

namespace Illuminate\Validation\Rules\Traits;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait ValidFileInstance
{
    /**
     * Check that the given value is a valid file instance.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isAValidFileInstance($value)
    {
        if ($value instanceof UploadedFile && ! $value->isValid()) {
            return false;
        }

        return $value instanceof File;
    }
}
