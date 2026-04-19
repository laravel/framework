<?php

namespace Illuminate\Contracts\Validation;

interface StrictMimeTypeGuesser
{
    /**
     * Guess the content type of a file and return its canonical extension.
     *
     * @param  string  $path
     * @return string|null
     */
    public function guess(string $path): ?string;
}
