<?php

namespace Illuminate\Http;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationData;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Transformable;

class TransformableResponse extends JsonResponse
{
    /**
     * Constructor.
     *
     * @param  mixed  $data
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @return void
     */
    public function __construct($data = null, $status = 200, $headers = [], $options = 0)
    {
        if (is_array($data)) {
            $transformedData = $this->transform($data);
        } elseif ($data instanceof Transformable) {
            $transformedData = $this->transform($this->getTransformableData());
        } elseif ($data instanceof Collection) {
            $transformedData = $this->handleCollectionTransformation($data);
        } elseif ($data instanceof Arrayable) {
            $transformedData = $this->transform($data->toArray());
        }

        parent::__construct($transformedData, $status, $headers, $options);

        $this->original = $data;
    }

    /**
     * Handle how a collection will be transformed.
     *
     * @param  \Illuminate\Support\Collection  $data
     * @return mixed
     */
    protected function handleCollectionTransformation(Collection $data)
    {
        return $this->transform(
            $data->map(function ($item) {
                if ($item instanceof Transformable) {
                    return $item->getTransformableData();
                } elseif ($item instanceof Arrayable) {
                    return $item->toArray();
                }

                return $item;
            })
            ->toArray()
        );
    }

    /**
     * Transforms the response data.
     *
     * @param  array  $data
     * @return array
     */
    protected function transform(array $data)
    {
        if (! empty($data) && method_exists($this, 'visibilityRules')) {
            $data = $this->applyVisibilityRules(
                $data,
                $this->visibilityRules()
            );
        }

        if (! empty($data) && method_exists($this, 'castingRules')) {
            $data = $this->applyCastingRules(
                $data,
                $this->castingRules()
            );
        }

        if (! empty($data) && method_exists($this, 'mutationRules')) {
            $data = $this->applyMutationRules(
                $data,
                $this->mutationRules()
            );
        }

        if (! empty($data) && method_exists($this, 'renamingRules')) {
            $data = $this->applyRenamingRules(
                $data,
                $this->renamingRules()
            );
        }

        return $data;
    }

    /**
     * Apply visibility rules to given data.
     *
     * @param  array  $data
     * @param  array  $rules
     * @return array
     */
    protected function applyVisibilityRules(array $data, array $rules)
    {
        if (empty($rules = $this->resolveWildcardRules($data, $rules))) {
            return $data;
        }

        $data = $this->performShowFields($data, $rules);
        $data = $this->performHideFields($data, $rules);

        return $data;
    }

    /**
     * Apply rules over the fields that must be displayed.
     *
     * @param  array  $data
     * @param  array  $rules
     * @return array
     */
    protected function performShowFields(array $data, array $rules)
    {
        if (empty($applicableRules = array_filter($rules))) {
            return $data;
        }

        return array_reduce(array_keys($applicableRules),
            function ($transformedData, $attribute) use ($data) {
                if ($value = Arr::get($data, $attribute)) {
                    Arr::set($transformedData, $attribute, $value);
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
    protected function performHideFields(array $data, array $rules)
    {
        $applicableRules = array_filter($rules, function ($rule) {
            return ! $rule;
        });

        return array_reduce(array_keys($applicableRules),
            function ($transformedData, $rule) {
                Arr::forget($transformedData, $rule);

                return $transformedData;
            },
        $data);
    }

    /**
     * Apply casting rules to given data.
     *
     * @param  array  $data
     * @param  array  $rules
     * @return array
     */
    protected function applyCastingRules(array $data, array $rules)
    {
        if (empty($rules = $this->resolveWildcardRules($data, $rules))) {
            return $data;
        }

        return array_reduce(array_keys($rules),
            function ($data, $rule) use ($rules) {
                if (Arr::has($data, $rule)) {
                    $value = $this->performCasting(
                        Arr::get($rules, $rule),
                        Arr::get($data, $rule)
                    );

                    Arr::set($data, $rule, $value);
                }

                return $data;
            },
        $data);
    }

    /**
     * Performs casting to given value based on given type.
     *
     * @param  string  $type
     * @param  mixed  $value
     * @return mixed
     */
    protected function performCasting($type, $value)
    {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            default:
                return $value;
        }
    }

    /**
     * Apply mutation rules to given value.
     *
     * @param  array  $data
     * @param  array  $rules
     * @return mixed
     */
    public function applyMutationRules(array $data, array $rules)
    {
        if (empty($rules = $this->resolveWildcardRules($data, $rules))) {
            return $data;
        }

        return array_reduce(array_keys($rules),
            function ($data, $rule) use ($rules) {
                if (Arr::has($data, $rule)) {
                    $value = $this->performMutations(
                        Arr::get($rules, $rule),
                        Arr::get($data, $rule)
                    );

                    Arr::set($data, $rule, $value);
                }

                return $data;
            },
        $data);
    }

    /**
     * Performs given mutators in given value.
     *
     * @param  string  $mutators
     * @param  mixed  $value
     * @throws Exception
     * @return mixed
     */
    public function performMutations($mutators, $value)
    {
        $mutators = explode('|', $mutators);

        return array_reduce($mutators, function ($value, $mutator) {
            $method = 'mutator'.Str::studly($mutator);

            if (! method_exists($this, $method)) {
                $class = static::class;

                throw new Exception("No mutator [$method] defined in [$class]");
            }

            return $this->$method($value);
        }, $value);
    }

    /**
     * Apply renaming rules for given attribute.
     *
     * @param  array  $data
     * @param  array  $rules
     * @return array
     */
    protected function applyRenamingRules(array $data, array $rules)
    {
        if (empty($rules = $this->resolveWildcardRules($data, $rules))) {
            return $data;
        }

        uksort($rules, function ($a, $b) {
            return count(explode('.', $a)) < count(explode('.', $b));
        });

        $resultData = $this->performRenaming($data, $rules);

        return array_reduce(array_keys($resultData),
            function ($data, $attribute) use ($resultData) {
                Arr::set($data, $attribute, $resultData[$attribute]);

                return $data;
            },
        []);
    }

    /**
     * Performs renaming mutators in given data.
     *
     * @param  array  $data
     * @param  array  $rules
     * @return array
     */
    protected function performRenaming(array $data, array $rules)
    {
        $resultData = array_reduce(array_keys($rules),
            function ($encodedData, $rule) use ($rules) {
                $replace = preg_replace(
                    '/(\w|\s|\-)+$/',
                    $rules[$rule],
                    $rule
                );

                return str_replace('"'.$rule, '"'.$replace, $encodedData);
            },
        json_encode(Arr::dot($data)));

        return json_decode($resultData, true);
    }

    /**
     * Resolve array rules generating a new rule for every '*' symbol.
     *
     * @param  array  $data
     * @param  array  $rules
     * @return array
     */
    protected function resolveWildcardRules(array $data, array $rules)
    {
        return array_reduce(array_keys($rules),
            function ($parsedRules, $rule) use ($data, $rules) {
                if (Str::contains($rule, '*')) {
                    $gatheredRules = array_keys(
                        ValidationData::initializeAndGatherData($rule, $data)
                    );

                    return array_merge(
                        $parsedRules,
                        $this->sanitizeWildcardGatheredRules(
                            $rule,
                            $gatheredRules,
                            $rules[$rule]
                        )
                    );
                }

                $parsedRules[$rule] = $rules[$rule];

                return $parsedRules;
            }, []);
    }

    /**
     * Sanitize rules removing those that don't appear into original rules.
     *
     * @param  string  $rule
     * @param  array  $gatheredRules
     * @param  bool  $valueForValidOnes
     * @return array
     */
    protected function sanitizeWildcardGatheredRules($rule, array $gatheredRules, $valueForValidOnes)
    {
        $pattern = '/'.str_replace('.*.', '\.([0-9])+\.', $rule).'/';

        return array_reduce($gatheredRules,
            function ($validRules, $rule) use ($pattern, $valueForValidOnes) {
                if (preg_match($pattern, $rule)) {
                    $validRules[$rule] = $valueForValidOnes;
                }

                return $validRules;
            },
        []);
    }
}
