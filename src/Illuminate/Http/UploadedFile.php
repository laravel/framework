<?php

namespace Illuminate\Http;

use ReflectionClass;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class UploadedFile extends SymfonyUploadedFile
{
    use Macroable;

    /**
     * Create a new file instance from a base instance.
     *
     * @param  \Symfony\Component\HttpFoundation\File\UploadedFile  $file
     * @param  bool  $testing
     * @return static
     */
    public static function createFromBase(SymfonyUploadedFile $file, $testing = false)
    {
        return $file instanceof static ? $file : new static(
            $file->getRealPath(), $file->getClientOriginalName(), $file->getClientMimeType(),
            $file->getClientSize(), $file->getError(), $testing
        );
    }
}
