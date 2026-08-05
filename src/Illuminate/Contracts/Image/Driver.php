<?php

namespace Illuminate\Contracts\Image;

use Illuminate\Image\ImagePipeline;

interface Driver
{
    /**
     * Process the given image contents with the specified pipeline.
     */
    public function process(string $contents, ImagePipeline $pipeline): string;

    /**
     * Get the dimensions of the given image contents.
     *
     * @return array{0: int, 1: int}
     */
    public function dimensions(string $contents): array;

    /**
     * Get the dominant (average) color of the image as a hex string.
     */
    public function dominantColor(string $contents): string;

    /**
     * Register a transformation handler.
     *
     * @param  class-string<\Illuminate\Contracts\Image\Transformation>  $transformation
     */
    public function transformUsing(string $transformation, callable $callback): static;
}
