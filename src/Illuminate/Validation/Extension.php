<?php

namespace Illuminate\Validation;

use ReflectionClass;
use ReflectionMethod;
use BadMethodCallException;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

abstract class Extension
{
    protected static $reflectors = [];
    
    protected static $replacers = [];
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    public static function getRules()
    {
        $methods = new Collection(self::getReflector()->getMethods(ReflectionMethod::IS_PUBLIC));
        $validation_methods = $methods->filter(function ($method) {
            return Str::startsWith($method->name, 'validate');
        });
        
        $rules = $validation_methods->map(function ($rule) {
            return Str::snake(str_replace('validate', '', $rule->name));
        });
        
        return $rules->all();
    }
    
    public static function getReplacers()
    {
        $methods = new Collection(self::getReflector()->getMethods(ReflectionMethod::IS_PUBLIC));
        $replacer_methods = $methods->filter(function ($method) {
            return Str::startsWith($method->name, 'replace');
        });
        
        $replacers = $replacer_methods->map(function ($replacer) {
            return Str::snake(str_replace('replace', '', $replacer->name));
        });
        
        return array_merge(array_keys(static::$replacers), $replacers->all());
    }
    
    private static function getReflector()
    {
        $class = get_called_class();
        
        if (! isset(static::$reflectors[$class])) {
            static::$reflectors[$class] = new ReflectionClass($class);
        }
        
        return static::$reflectors[$class];
    }
    
    public function __call($method, $arguments)
    {
        $rule = Str::snake(str_replace('replace', '', $method));
        
        if (isset($this->replacers[$rule])) {
            list($message) = $arguments;
            list($placeholders, $replacements) = array_divide($this->replacers[$rule]);
            
            return str_replace($placeholders, $replacements, $message);
        }
        
        throw new BadMethodCallException("Method [$method] does not exist.");
    }
}