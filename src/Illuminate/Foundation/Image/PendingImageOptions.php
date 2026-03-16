<?php

namespace Illuminate\Foundation\Image;

class PendingImageOptions
{
    /**
     * The cover width.
     */
    public ?int $coverWidth = null;

    /**
     * The cover height.
     */
    public ?int $coverHeight = null;

    /**
     * The output format.
     */
    public ?string $format = null;

    /**
     * The output quality.
     */
    public ?int $quality = null;

    /**
     * Determine if any options have been set.
     */
    public function hasChanges(): bool
    {
        return count(array_filter((array) $this)) > 0;
    }
}
