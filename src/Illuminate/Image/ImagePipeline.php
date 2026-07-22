<?php

namespace Illuminate\Image;

use Illuminate\Contracts\Image\Transformation;

class ImagePipeline
{
    /**
     * The ordered image transformations.
     *
     * @var array<int, \Illuminate\Contracts\Image\Transformation>
     */
    public array $transformations = [];

    /**
     * Create a new image pipeline instance.
     */
    public function __construct(public ImageOutputOptions $output = new ImageOutputOptions)
    {
        //
    }

    /**
     * Add a transformation to the pipeline.
     */
    public function add(Transformation $transformation): void
    {
        $this->transformations[] = $transformation;
    }

    /**
     * Determine if the pipeline has transformations or output changes.
     */
    public function hasChanges(): bool
    {
        return $this->transformations !== [] || $this->output->hasChanges();
    }

    /**
     * Clone the output options with the pipeline.
     */
    public function __clone(): void
    {
        $this->output = clone $this->output;
    }
}
