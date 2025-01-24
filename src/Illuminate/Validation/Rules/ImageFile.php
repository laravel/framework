<?php

namespace Illuminate\Validation\Rules;

class ImageFile extends File
{
    public static $allowSvgByDefault = true;

    public static function allowSvg($allowByDefault = true)
    {
        static::$allowSvgByDefault = $allowByDefault;
    }

    /**
     * Create a new image file rule instance.
     *
     * @param  bool|null  $allowSvg
     * @return void
     */
    public function __construct($allowSvg = null)
    {
        $allowSvg ??= static::$allowSvgByDefault;

        if ($allowSvg) {
            $this->rules('image:allow_svg');
        } else {
            $this->rules('image');
        }
    }

    /**
     * The dimension constraints for the uploaded file.
     *
     * @param  \Illuminate\Validation\Rules\Dimensions  $dimensions
     * @return $this
     */
    public function dimensions($dimensions)
    {
        $this->rules($dimensions);

        return $this;
    }
}
