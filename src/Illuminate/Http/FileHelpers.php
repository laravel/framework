<?php

namespace Illuminate\Http;

use Illuminate\Support\Str;

trait FileHelpers
{
    /**
     * The cache copy of the file's hash name.
     *
     * @var string
     */
    protected $hashName = null;

    /**
     * Get the file's extension.
     *
     * @return string
     */
    public function extension()
    {
        return $this->guessExtension();
    }

    /**
     * Get a filename for the file.
     *
     * @return string
     */
    public function hashName()
    {
        if (! $this->hashName) {
            $this->hashName = Str::random(40);
        }

        $extension = $this->guessExtension() ?? $this->getClientOriginalExtension();

        return $this->hashName.'.'.$extension;
    }
}
