<?php

namespace Illuminate\Foundation\Image\Drivers;

use finfo;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Foundation\Image\ImageException;
use Illuminate\Foundation\Image\PendingImageOptions;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\MediaTypeEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

abstract class InterventionDriver implements Driver
{
    /**
     * The Intervention image manager instance.
     */
    protected ImageManager $manager;

    /**
     * Create a new Intervention driver instance.
     */
    public function __construct()
    {
        $this->manager = $this->createManager();
    }

    /**
     * Create the underlying Intervention image manager.
     */
    abstract protected function createManager(): ImageManager;

    /**
     * Ensure Intervention Image is installed.
     *
     * @throws ImageException
     */
    public function ensureRequirementsAreMet(): void
    {
        if (! class_exists(ImageManager::class)) {
            throw new ImageException(
                'Intervention Image is required to use this driver. '.
                'You may install it via: composer require intervention/image:^3.11.7',
            );
        }
    }

    /**
     * Process the given image contents with the specified options.
     */
    public function process(string $contents, PendingImageOptions $options): string
    {
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/bmp', 'image/gif', 'image/webp'])) {
            throw new ImageException("The image format [{$mimeType}] is not supported.");
        }

        $image = $this->manager->read($contents);

        if ($options->orient) {
            $image = $image->orient();
        }

        if ($options->coverWidth !== null && $options->coverHeight !== null) {
            $image = $image->cover($options->coverWidth, $options->coverHeight);
        }

        if ($options->scaleWidth !== null) {
            $image = $image->scaleDown($options->scaleWidth, $options->scaleHeight);
        }

        if ($options->blur !== null) {
            $image = $image->blur($options->blur);
        }

        if ($options->greyscale) {
            $image = $image->greyscale();
        }

        if ($options->sharpen !== null) {
            $image = $image->sharpen($options->sharpen);
        }

        if ($options->flip) {
            $image = $image->flip();
        }

        if ($options->flop) {
            $image = $image->flop();
        }

        $quality = $options->quality ?? PendingImageOptions::DEFAULT_QUALITY;

        try {
            if ($options->format !== null) {
                return $image->encode(match ($options->format) {
                    'webp' => new WebpEncoder($quality),
                    'jpg', 'jpeg' => new JpegEncoder($quality),
                })->toString();
            }

            $mediaType = match ($image->origin()->mediaType()) {
                'image/x-gif' => 'image/gif',
                default => null,
            };

            return $image->encode(new MediaTypeEncoder($mediaType, quality: $quality))->toString();
        } finally {
            unset($image);
        }
    }
}
