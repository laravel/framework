<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DistinctRule extends Rule
{
    use Traits\ExtractDataFromPath;

    /**
     * {@inheritdoc}
     */
    public function passes($attribute, $value, $parameters, $validator)
    {
        $primary = $validator->getPrimaryAttribute($attribute);
        $explicitPath = $this->getLeadingExplicitAttributePath($primary);
        $attributeData = $this->extractDataFromPath($explicitPath, $validator);

        $data = Arr::where(Arr::dot($attributeData), function ($value, $key) use ($attribute, $primary) {
            return $key != $attribute && Str::is($primary, $key);
        });

        return ! in_array($value, array_values($data));
    }
}
