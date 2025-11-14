<?php

namespace Illuminate\Http\Concerns;

use Illuminate\Support\Collection;

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

        $validateOnly = explode(',', $this->header('Precognition-Validate-Only'));
        $validateOnlyPatterns = (new Collection($validateOnly))
            ->map(fn ($pattern) => '/^'.str_replace('\*', '\d+', preg_quote($pattern, '/')).'$/');

        return (new Collection($rules))
            ->filter(function ($rule, $ruleKey) use ($validateOnlyPatterns) {
                foreach ($validateOnlyPatterns as $pattern) {
                    if (preg_match($pattern, $ruleKey)) {
                        return true;
                    }
                }

                return false;
            })->all();
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
