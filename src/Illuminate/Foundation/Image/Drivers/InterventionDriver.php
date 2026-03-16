<?php

namespace Illuminate\Foundation\Image\Drivers;

use Illuminate\Contracts\Image\Driver;
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
     * Process an image at the given path with the specified options.
     */
    public function process(string $sourcePath, PendingImageOptions $options): string
    {
        $image = $this->manager->read($sourcePath);

        if ($options->coverWidth !== null && $options->coverHeight !== null) {
            $image = $image->cover($options->coverWidth, $options->coverHeight);
        }

        if ($options->format !== null) {
            $encoder = match ($options->format) {
                'webp' => new WebpEncoder($options->quality),
                'png' => new PngEncoder,
                'gif' => new GifEncoder,
                'jpg', 'jpeg' => new JpegEncoder($options->quality),
                default => new AutoEncoder($options->quality),
            };

            return $image->encode($encoder)->toString();
        }

        return $image->encode(new AutoEncoder)->toString();
    }
}
