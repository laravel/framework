<?php

namespace Illuminate\Contracts\Http;

interface UploadedFile
{
    /**
     * Get the fully qualified path to the file.
     *
     * @return string
     */
    public function path();

    /**
     * Get the file's extension.
     *
     * @return string
     */
    public function extension();
}
