<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class DimensionsRule extends Rule
{
    use Traits\ValidFileInstance;

    /**
     * {@inheritdoc}
     */
    protected $requiredParametersCount = 1;

    /**
     * {@inheritdoc}
     */
    protected $allowNamedParameters = true;

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        if (! $this->isAValidFileInstance($value) || ! $sizeDetails = getimagesize($value->getRealPath())) {
            return false;
        }

        list($width, $height) = $sizeDetails;

        if (
            isset($parameters['width']) && $parameters['width'] != $width ||
            isset($parameters['min_width']) && $parameters['min_width'] > $width ||
            isset($parameters['max_width']) && $parameters['max_width'] < $width ||
            isset($parameters['height']) && $parameters['height'] != $height ||
            isset($parameters['min_height']) && $parameters['min_height'] > $height ||
            isset($parameters['max_height']) && $parameters['max_height'] < $height
        ) {
            return false;
        }

        if (isset($parameters['ratio'])) {
            list($numerator, $denominator) = array_replace(
                [1, 1], array_filter(sscanf($parameters['ratio'], '%f/%d'))
            );

            return $numerator / $denominator == $width / $height;
        }

        return true;
    }
}
