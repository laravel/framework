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

    /**
     * Get the file size in bytes.
     *
     * @return int
     */
    public function size();

    /**
     * Get the file mime type.
     *
     * @return string
     */
    public function mimeType();

    /**
     * Returns whether the file was uploaded successfully.
     *
     * @return bool
     */
    public function isValid();
}
