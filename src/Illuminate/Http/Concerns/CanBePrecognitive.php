<?php

namespace Illuminate\Http\Concerns;

use Illuminate\Support\Collection;

trait CanBePrecognitive
{
    /**
     * Filter the given array of rules into an array of rules that are included in precognitive headers.
     *
     * @param array $rules
     * @return array
     */
    public function filterPrecognitiveRules(array $rules): array
    {
        if (! $this->headers->has('Precognition-Validate-Only')) {
            return $rules;
        }

        return Collection::make($rules)
            ->only($this->getPrecognitionValidate())
            ->all();
    }

    /**
     * Determine if the request is attempting to be precognitive.
     *
     * @return bool
     */
    public function isAttemptingPrecognition(): bool
    {
        return $this->header('Precognition') === 'true';
    }

    /**
     * Determine if the request is precognitive.
     *
     * @return bool
     */
    public function isPrecognitive()
    {
        return $this->attributes->get('precognitive', false);
    }

    /**
     * Return Precognition-Validate-Only header data as an array.
     *
     * @return array
     */
    public function getPrecognitionValidate(): array
    {
        return explode(',', $this->header('Precognition-Validate-Only'));
    }
}
