<?php

namespace Illuminate\Http;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationData;
use Illuminate\Contracts\Support\Arrayable;

abstract class TransformableResponse extends JsonResponse
{
    /**
     * Constructor.
     *
     * @param  mixed  $data
     * @param  int    $status
     * @param  array  $headers
     * @param  int    $options
     */
    public function __construct($data = null, $status = 200, $headers = [], $options = 0)
    {
        if (is_array($data)) {
            $data = $this->transform($data);
        } elseif ($data instanceof Arrayable) {
            $data = $this->transform($data->toArray());
        }

        parent::__construct($data, $status, $headers, $options);
    }

    /**
     * Transforms the response data.
     *
     * @param  array  $data
     * @return array
     */
    public function transform(array $data)
    {
        $rules = $this->resolveArrayRules($data, $this->rules());

        return $this->applyRules($data, $rules);
    }

    /**
     * Resolve array rules generating a new rule for every item in the array
     * spcified with the '*' symbol.
     *
     * Example: posts.*.comments.*.title => posts.0.comments.0.title
     *
     * @param  array  $data
     * @param  array  $rules
     * @return array
     */
    protected function resolveArrayRules(array $data, array $rules)
    {
        return array_reduce(array_keys($rules), function ($parsedRules, $rule) use ($data, $rules) {
            if (Str::contains($rule, '*')) {
                $gatheredRules = array_keys(
                    ValidationData::initializeAndGatherData($rule, $data)
                );

                return array_merge(
                    $parsedRules,
                    $this->sanitizeArrayGatheredRules($rule, $gatheredRules, $rules[$rule])
                );
            }

            $parsedRules[$rule] = $rules[$rule];

            return $parsedRules;
        }, []);
    }

    /**
     * Sanitize gathered array rules removing those that don't appear into
     * orginal rules.
     *
     * @param  string   $rule
     * @param  array    $gatheredRules
     * @param  bool  $valueForValidOnes
     * @return array
     */
    protected function sanitizeArrayGatheredRules($rule, array $gatheredRules, $valueForValidOnes)
    {
        $pattern = '/'.str_replace('.*.', '\.([0-9])+\.', $rule).'/';

        return array_reduce($gatheredRules,
            function ($validRules, $rule) use ($pattern, $valueForValidOnes) {
                preg_match($pattern, $rule, $matches);

                if ($matches) {
                    $validRules[$matches[0]] = $valueForValidOnes;
                }

                return $validRules;
            },
        []);
    }

    /**
     * Apply rules to the initial given data.
     *
     * @param  array  $rules
     * @return array
     */
    protected function applyRules(array $data, array $rules)
    {
        if (empty($rules)) {
            return $data;
        }

        $data = $this->applyRulesToShowFields($data, $rules);
        $data = $this->applyRulesToHideFields($data, $rules);

        return $data;
    }

    /**
     * Apply rules over the fields that must be displayed.
     *
     * @param  array  $data
     * @param  array  $rules
     * @return array
     */
    protected function applyRulesToShowFields(array $data, array $rules)
    {
        $applicableRules = array_filter($rules);

        if (empty($applicableRules)) {
            return $data;
        }

        return array_reduce(array_keys($applicableRules),
            function ($transformedData, $rule) use ($data) {
                if ($value = Arr::get($data, $rule)) {
                    Arr::set($transformedData, $rule, $value);
                }

                return $transformedData;
            },
        []);
    }

    /**
     * Apply rules over the fields that must be hidden.
     *
     * @param  array  $data
     * @param  array  $rules
     * @return array
     */
    protected function applyRulesToHideFields(array $data, array $rules)
    {
        $applicableRules = array_filter($rules, function ($rule) {
            return ! $rule;
        });

        if (empty($applicableRules)) {
            return $data;
        }

        return array_reduce(array_keys($applicableRules),
            function ($transformedData, $rule) {
                if (Arr::has($transformedData, $rule)) {
                    Arr::forget($transformedData, $rule);
                }

                return $transformedData;
            },
        $data);
    }

    /**
     * Define the rules that will apply to this transformer
     * response.
     *
     * @return array
     */
    abstract public function rules();
}
