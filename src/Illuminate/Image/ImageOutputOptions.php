<?php

namespace Illuminate\Image;

class ImageOutputOptions
{
    /**
     * The default output quality.
     */
    const DEFAULT_QUALITY = 75;

    /**
     * The output format.
     *
     * @var 'webp'|'jpg'|'jpeg'|null
     */
    public ?string $format = null;

    /**
     * The output quality (1-100).
     *
     * @var int<1, 100>|null
     */
    public ?int $quality = null;

    /**
     * Determine if any output options have been set.
     */
    public function hasChanges(): bool
    {
        return $this->format !== null || $this->quality !== null;
    }
}
