<?php

namespace Illuminate\Http;

use Illuminate\Support\Str;

trait FileHelpers
{
    /**
     * The cache copy of the file's hash name.
     *
     * @var string|null
     */
    protected string|null $hashName = null;

    /**
     * Get the fully qualified path to the file.
     *
     * @return string
     */
    public function path(): string
    {
        return $this->getRealPath();
    }

    /**
     * Get the file's extension.
     *
     * @return string
     */
    public function extension(): string
    {
        return $this->guessExtension();
    }


    /**
     * Get a filename for the file.
     *
     * @param  string|null  $path
     * @return string
     */
    public function hashName(string|null $path = null): string
    {
        $path = $path ? rtrim($path, '/').'/' : '';
        $hash = $this->hashName ??= Str::random(40);
        $extension = $this->guessExtension() ? '.'.$this->guessExtension() : '';

        return "{$path}{$hash}{$extension}";
    }

    /**
     * Get the dimensions of the image (if applicable).
     *
     * @return array|null
     */
    public function dimensions(): ?array
    {
        try {
            $size = getimagesize($this->getRealPath());
            return $size !== false ? $size : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
