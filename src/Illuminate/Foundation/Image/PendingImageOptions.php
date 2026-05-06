<?php

namespace Illuminate\Foundation\Image;

class PendingImageOptions
{
    /**
     * The default output quality.
     */
    const DEFAULT_QUALITY = 75;

    /**
     * The cover width.
     *
     * @var int<1, max>|null
     */
    public ?int $coverWidth = null;

    /**
     * The cover height.
     *
     * @var int<1, max>|null
     */
    public ?int $coverHeight = null;

    /**
     * The scale width.
     *
     * @var int<1, max>|null
     */
    public ?int $scaleWidth = null;

    /**
     * The scale height.
     *
     * @var int<1, max>|null
     */
    public ?int $scaleHeight = null;

    /**
     * Whether to auto-orient the image based on EXIF data.
     */
    public ?true $orient = null;

    /**
     * The blur amount.
     *
     * @var int<0, 100>|null
     */
    public ?int $blur = null;

    /**
     * Whether to convert the image to greyscale.
     */
    public ?true $greyscale = null;

    /**
     * The sharpen amount.
     *
     * @var int<0, 100>|null
     */
    public ?int $sharpen = null;

    /**
     * Whether to flip the image vertically.
     */
    public ?true $flip = null;

    /**
     * Whether to flip the image horizontally.
     */
    public ?true $flop = null;

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
     * Determine if any options have been set.
     */
    public function hasChanges(): bool
    {
        return count(array_filter((array) $this, fn (mixed $value) => $value !== null)) > 0;
    }
}
