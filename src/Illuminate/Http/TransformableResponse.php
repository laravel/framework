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
     * @param  array $data
     * @return array
     */
    public function transform(array $data)
    {
        $rules = $this->resolveArrayVisibilityRules($data, $this->visibilityRules());

        $data = $this->applyVisibilityRules($data, $rules);

        return $data;
    }
    
    /**
     * Resolve array rules generating a new rule for every item in the array
     * spcified with the '*' symbol.
     *
     * Example: posts.*.comments.*.title => posts.0.comments.0.title
     *
     * @param  array $data
     * @param  array $rules
     * @return array
     */
    protected function resolveArrayVisibilityRules(array $data, array $rules)
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
     * @param  string $rule
     * @param  array $gatheredRules
     * @param  boolean $valueForValidOnes
     * @return array
     */
    protected function sanitizeArrayGatheredRules($rule, array $gatheredRules, $valueForValidOnes)
    {
        $pattern = '/' . str_replace('.*.', '\.([0-9])+\.', $rule) . '/';
        
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
     * Apply visbility rules to given data.
     *
     * @param  array $rules
     * @return array
     */
    protected function applyVisibilityRules(array $data, array $rules)
    {
        if (empty($rules)) {
            return $data;
        }

        $data = $this->showFields($data, $rules);
        $data = $this->hideFields($data, $rules);

        return $data;
    }
    
    /**
     * Apply rules over the fields that must be displayed.
     *
     * @param  array $data
     * @param  array $rules
     * @return array
     */
    protected function showFields(array $data, array $rules)
    {
        $applicableRules = array_filter($rules);

        if (empty($applicableRules)) {
            return $data;
        }

        return array_reduce(array_keys($applicableRules),
            function ($transformedData, $attribute) use ($data) {
                if ($value = Arr::get($data, $attribute)) {
                    Arr::set(
                        $transformedData,
                        $this->applyRenamingRules($attribute),
                        $this->applyMutationRules(
                            $attribute,
                            $this->applyCastingRules($attribute, $value)
                        )
                    );
                }
                
                return $transformedData;
            },
        []);
    }
    
    /**
     * Apply rules over the fields that must be hidden.
     *
     * @param  array $data
     * @param  array $rules
     * @return array
     */
    protected function hideFields(array $data, array $rules)
    {
        $applicableRules = array_filter($rules, function ($rule) {
            return !$rule;
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
     * Apply casting rules to given value.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return mixed
     */
    protected function applyCastingRules($attribute, $value)
    {
        switch ($this->getCastType($attribute)) {
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
     * Get casting rule for given attribute.
     *
     * @param  string  $attribute
     * @return boolean
     */
    protected function getCastType($attribute)
    {
        if (!$castings = $this->castingRules()) {
            return null;
        }
        
        if (array_key_exists($attribute, $castings)) {
            return $castings[$attribute];
        }
        
        $pattern = preg_replace('/\.([0-9])+\./', '.*.', $attribute);
        
        if (array_key_exists($pattern, $castings)) {
            return $castings[$pattern];
        }
        
        return null;
    }
    
    /**
     * Apply renaming rules for given attribute.
     *
     * @param  string  $attribute
     * @return string
     */
    protected function applyRenamingRules($attribute)
    {
        if (!$renamings = $this->renamingRules()) {
            return $attribute;
        }

        if (array_key_exists($attribute, $renamings)) {
            return $renamings[$attribute];
        }
        
        $pattern = preg_replace('/\.([0-9])+\./', '.*.', $attribute);

        if (array_key_exists($pattern, $renamings)) {
            return preg_replace('/(\w|\s|\-)+$/', $renamings[$pattern], $attribute);
        }
        
        return $attribute;
    }
    
    /**
     * Apply mutation rules to given value.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return mixed
     */
    public function applyMutationRules($attribute, $value)
    {
        $mutations = $this->mutationRules();
        
        if (array_key_exists($attribute, $mutations)) {
            return $this->executeMutators($value, $mutations[$attribute]);
        }
        
        $pattern = preg_replace('/\.([0-9])+\./', '.*.', $attribute);

        if (array_key_exists($pattern, $mutations)) {
            return $this->executeMutators($value, $mutations[$pattern]);
        }
        
        return $value;
    }
    
    /**
     * Execute mutators in given value.
     *
     * @param  mixed  $value
     * @param  string  $mutators
     * @return mixed
     */
    public function executeMutators($value, $mutators)
    {
        $mutators = explode('|', $mutators);
        
        return array_reduce($mutators, function ($value, $mutator) {
            $method = 'mutator'.Str::studly($mutator);
            
            if (!method_exists($this, $method)) {
                $classname = static::class;
                
                throw new \Exception("There is no mutator [$method] declared in [$classname].");
            }
            
            return $this->$method($value);
        }, $value);
    }
    
    /**
     * Set visibility rules to apply to current response.
     *
     * @return array
     */
    abstract public function visibilityRules();
    
    /**
     * Set casting rules to apply to current response.
     *
     * @return array
     */
    abstract public function castingRules();
    
    /**
     * Set renaming rules to apply to current response.
     *
     * @return array
     */
    abstract public function renamingRules();
    
    /**
     * Set mutation rules to apply to current response.
     *
     * @return array
     */
    abstract public function mutationRules();
}
