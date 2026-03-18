<?php

namespace Illuminate\Foundation\Image\Drivers;

use Illuminate\Contracts\Image\Driver;
use Illuminate\Foundation\Image\ImageException;
use Illuminate\Foundation\Image\PendingImageOptions;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
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
                'You may install it via: composer require intervention/image:^3.0',
            );
        }
    }

    /**
     * Process the given image contents with the specified options.
     */
    public function process(string $contents, PendingImageOptions $options): string
    {
        $image = $this->manager->read($contents);

        if ($options->coverWidth !== null && $options->coverHeight !== null) {
            $image = $image->cover($options->coverWidth, $options->coverHeight);
        }

        if ($options->format !== null) {
            $quality = $options->quality ?? -1;

            $encoder = match ($options->format) {
                'webp' => new WebpEncoder($quality),
                'png' => new PngEncoder,
                'gif' => new GifEncoder,
                'jpg', 'jpeg' => new JpegEncoder($quality),
                default => new AutoEncoder($quality),
            };

            return $image->encode($encoder)->toString();
        }

        return $image->encode(new AutoEncoder)->toString();
    }
}
