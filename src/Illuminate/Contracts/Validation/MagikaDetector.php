<?php

namespace Illuminate\Contracts\Validation;

interface MagikaDetector
{
    /**
     * Detect the content type of a file and return its canonical extension.
     *
     * @param  string  $path
     * @return string|null
     */
    public function detect(string $path): ?string;
}
