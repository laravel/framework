<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class InArrayRule extends Rule
{
    use Traits\ExtractDataFromPath;
    /**
     * {@inheritdoc}
     */
    protected $requiredParametersCount = 1;

    /**
     * {@inheritdoc}
     */
    public function mapParameters($parameters)
    {
        return [
            'other_field' => array_shift($parameters),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        $primary = $validator->getPrimaryAttribute($attribute);
        $explicitPath = $this->getLeadingExplicitAttributePath($attribute);
        $attributeData = $this->extractDataFromPath($explicitPath, $validator);

        $otherValues = Arr::where(Arr::dot($attributeData), function ($value, $key) use ($parameters) {
            return Str::is($parameters['other_field'], $key);
        });

        return in_array($value, $otherValues);
    }
}
