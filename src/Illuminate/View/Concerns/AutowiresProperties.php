<?php

namespace Illuminate\View\Concerns;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Illuminate\Support\Str;

trait AutowiresProperties
{
    /**
     * Automatically assign values to any properties on the class whose name
     * exists in the supplied attributes, then call the mount() method.
     *
     * @return array
     */
    public function autowireProperties()
    {
        $class = new ReflectionClass($this);
        $allAttributes = $this->attributes->getAttributes();

        $properties = collect($class->getProperties(ReflectionMethod::IS_PUBLIC))
            ->filter(function (ReflectionProperty $property) {
                return !in_array($property->name, ['componentName', 'attributes']);
            })
            ->map(function (ReflectionProperty $property) {
                return Str::camel($property->getName());
            })
            ->toArray();

        foreach ($properties as $property) {
            if (isset($this->attributes[$property])) {
                $this->{$property} = $this->attributes[$property];
                unset($this->attributes[$property]);
            }
        }

        if (method_exists($this, 'mount')) {
            app()->call([$this, 'mount'], $allAttributes);
        }

        return $this->data();
    }
}