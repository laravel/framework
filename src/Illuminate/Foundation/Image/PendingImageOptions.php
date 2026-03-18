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
     * The scale width.
     */
    public ?int $scaleWidth = null;

    /**
     * The scale height.
     */
    public ?int $scaleHeight = null;

    /**
     * Whether to auto-orient the image based on EXIF data.
     */
    public ?true $orient = null;

    /**
     * The blur amount.
     */
    public ?int $blur = null;

    /**
     * Whether to convert the image to greyscale.
     */
    public ?true $greyscale = null;

    /**
     * The output format.
     */
    public ?string $format = null;

    /**
     * The output quality (1-100).
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
