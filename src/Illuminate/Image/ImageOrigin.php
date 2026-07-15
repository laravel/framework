<?php

namespace Illuminate\Image;

use Stringable;

class ImageOrigin implements Stringable
{
    /**
     * Create a new image origin instance.
     *
     * @param  'path'|'url'|'storage'|'upload'|'bytes'|'base64'  $type
     */
    public function __construct(
        public string $type,
        public ?string $reference = null,
        public ?string $disk = null,
    ) {
    }

    /**
     * Get the string representation of the origin.
     */
    public function __toString(): string
    {
        return implode(':', array_filter(
            [$this->type, $this->disk, $this->reference],
            fn ($value) => ! is_null($value),
        ));
    }
}
