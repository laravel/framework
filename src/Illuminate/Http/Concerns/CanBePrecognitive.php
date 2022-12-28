<?php

namespace Illuminate\Http\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationRuleParser;

trait CanBePrecognitive
{
    /**
     * Filter the given array of rules into an array of rules that are included in precognitive headers.
     *
     * @param  array  $rules
     * @return array
     */
    public function filterPrecognitiveRules($rules)
    {
        if (! $this->headers->has('Precognition-Validate-Only')) {
            return $rules;
        }

        return Collection::make($rules)
            ->only(explode(',', $this->header('Precognition-Validate-Only')))
            ->all();
    }

    /**
     * Parse the human-friendly rules into a full rules array.
     *
     * @param  array  $rules
     * @param  array  $data
     * @return \stdClass
     */
    public function explodePrecognitiveRules($rules, $data)
    {
        return (new ValidationRuleParser($data))->explode($rules);
    }

    /**
     * Determine if the request is attempting to be precognitive.
     *
     * @return bool
     */
    public function isAttemptingPrecognition()
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
}
