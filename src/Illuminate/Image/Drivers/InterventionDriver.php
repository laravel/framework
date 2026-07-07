<?php

namespace Illuminate\Image\Drivers;

use finfo;
use Illuminate\Contracts\Image\Driver;
use Illuminate\Image\ImageException;
use Illuminate\Image\ImageOutputOptions;
use Illuminate\Image\ImagePipeline;
use Illuminate\Image\Transformations\Blur;
use Illuminate\Image\Transformations\Cover;
use Illuminate\Image\Transformations\FlipHorizontally;
use Illuminate\Image\Transformations\FlipVertically;
use Illuminate\Image\Transformations\Greyscale;
use Illuminate\Image\Transformations\Orient;
use Illuminate\Image\Transformations\Scale;
use Illuminate\Image\Transformations\Sharpen;
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
     * Process the given image contents with the specified pipeline.
     */
    public function process(string $contents, ImagePipeline $pipeline): string
    {
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/bmp', 'image/gif', 'image/webp'])) {
            throw new ImageException("The image format [{$mimeType}] is not supported.");
        }

        $image = $this->manager->read($contents);

        foreach ($pipeline->transformations as $transformation) {
            $image = match (true) {
                $transformation instanceof Orient => $image->orient(),
                $transformation instanceof Cover => $image->cover($transformation->width, $transformation->height),
                $transformation instanceof Scale => $image->scaleDown($transformation->width, $transformation->height),
                $transformation instanceof Blur => $image->blur($transformation->amount),
                $transformation instanceof Greyscale => $image->greyscale(),
                $transformation instanceof Sharpen => $image->sharpen($transformation->amount),
                $transformation instanceof FlipVertically => $image->flip(),
                $transformation instanceof FlipHorizontally => $image->flop(),
                default => throw new ImageException('The image transformation ['.get_class($transformation).'] is not supported.'),
            };
        }

        $quality = $pipeline->output->quality ?? ImageOutputOptions::DEFAULT_QUALITY;

        try {
            if ($pipeline->output->format !== null) {
                return $image->encode(match ($pipeline->output->format) {
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
