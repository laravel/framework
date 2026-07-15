<?php

namespace Illuminate\Image\Events;

use Illuminate\Image\ImageOrigin;
use Illuminate\Image\ImagePipeline;

class ImageProcessed
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Image\ImageOrigin|null  $origin  The origin of the image.
     * @param  string  $driver  The name of the driver that processed the image.
     * @param  \Illuminate\Image\ImagePipeline  $pipeline  The pipeline of applied transformations.
     * @param  int  $inputSize  The image size in bytes before processing.
     * @param  int  $outputSize  The image size in bytes after processing.
     * @param  float  $time  The number of milliseconds it took to process the image.
     */
    public function __construct(
        public ?ImageOrigin $origin,
        public string $driver,
        public ImagePipeline $pipeline,
        public int $inputSize,
        public int $outputSize,
        public float $time,
    ) {
    }
}
