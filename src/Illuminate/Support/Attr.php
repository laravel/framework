<?php

namespace Illuminate\Support;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;

class Attr
{
    /**
     * Create a new Attr instance.
     *
     * @param  \ReflectionClass|\ReflectionObject|\ReflectionMethod|\ReflectionProperty  $reflection
     * @param  int  $flags
     * @param  bool $isRecursive
     * @param  bool $isFindingAll
     */
    public function __construct(
        protected $reflection,
        protected $flags = 0,
        protected $isRecursive = false,
        protected $isFindingAll = false
    ) {
        //
    }

    /**
     * Include descendants of the attribute to filter.
     *
     * @return $this
     */
    public function instancesOf()
    {
        $this->flags |= ReflectionAttribute::IS_INSTANCEOF;

        return $this;
    }

    /**
     * Recursively find all classes until the last parent.
     *
     * @param  bool  $findAll
     * @return $this
     */
    public function recursive($findAll = false)
    {
        $this->isRecursive = true;
        $this->isFindingAll = $findAll;

        return $this;
    }

    /**
     * Retrieve all the attribute reflections of the target.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     * @return \ReflectionAttribute<TAttribute>[]
     */
    public function all($attribute)
    {
        // When the developer asks for recursive discovery, we will also check if the reflection
        // supports recursion. As we search all the matching attributes, we will stop only when
        // there are attributes found, or we reach the deepest parent class possible from all.
        $shouldFindParents = $this->isRecursive && (
            $this->reflection instanceof ReflectionClass ||
            $this->reflection instanceof ReflectionMethod ||
            $this->reflection instanceof ReflectionProperty
        );

        $parent = $this->reflection;

        $reflections = [];

        do {
            $reflections = array_merge($reflections, $parent->getAttributes($attribute, $this->flags));
        } while (
            (empty($reflections) || $this->isFindingAll) &&
            // When the developer has for a recursive search on methods or properties, we will
            // find the declaring parent class, check if the method or property exists in it,
            // and give the respective reflection. This way we can keep the recursion logic.
            $parent = $shouldFindParents ? $this->getParent($parent) : null
        );

        return $reflections;
    }

    /**
     * Retrieves the parent class of the class, object, property, or method.
     *
     * @param \ReflectionClass|\ReflectionMethod|\ReflectionProperty  $reflection
     * @return \ReflectionClass|\ReflectionMethod|\ReflectionProperty|null
     */
    protected function getParent($reflection)
    {
        if ($reflection instanceof ReflectionClass) {
            return $reflection->getParentClass() ?: null;
        }

        if ($reflection instanceof ReflectionMethod) {
            $parent = $reflection->getDeclaringClass()->getParentClass();

            if ($parent && $parent->hasMethod($reflection->getName())) {
                return $parent->getMethod($reflection->getName());
            }

            return null;
        }

        if ($reflection instanceof ReflectionProperty) {
            $parent = $reflection->getDeclaringClass()->getParentClass();

            if ($parent && $parent->hasProperty($reflection->getName())) {
                return $parent->getProperty($reflection->getName());
            }

            return null;
        }
    }

    /**
     * Returns all the attributes as their respective object instances.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     * @return TAttribute[]
     */
    public function instances($attribute)
    {
        return array_map(fn($attribute) => $attribute->newInstance(), $this->all($attribute));
    }

    /**
     * Return all the instances of the attribute as a collection.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     * @return \Illuminate\Support\Collection<TAttribute>
     */
    public function collect($attribute)
    {
        return new Collection($this->instances($attribute));
    }

    /**
     * Retrieve the first Reflection Attribute instance of the target.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     * @return \ReflectionAttribute<TAttribute>|null
     */
    public function first($attribute)
    {
        return $this->all($attribute)[0] ?? null;
    }

    /**
     * Retrieve the first attribute instance of the target.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     * @return TAttribute|null
     */
    public function instance($attribute)
    {
        return $this->first($attribute)?->newInstance();
    }

    /**
     * Check if a given attribute class exists on the target.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     * @return bool
     */
    public function has($attribute)
    {
        return !empty($this->all($attribute));
    }

    /**
     * Check if a given attribute class does not exist on the target.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     * @return bool
     */
    public function missing($attribute)
    {
        return !$this->has($attribute);
    }

    /**
     * Retrieve all the attributes from the given target.
     *
     * @param  class-string|object  $class
     * @return static
     */
    public static function onClass($class)
    {
        return new static(new ReflectionClass($class));
    }

    /**
     * Retrieve all the attributes from the given object.
     *
     * @param  object  $object
     * @return static
     */
    public static function onObject($object)
    {
        return new static(new ReflectionObject($object));
    }

    /**
     * Retrieve all the attributes from the given method.
     *
     * @param  object|string  $target
     * @param  string  $method
     * @return static
     */
    public static function onMethod($target, $method)
    {
        return new static(new ReflectionMethod($target, $method));
    }

    /**
     * Retrieve all the attributes from the given property.
     *
     * @param  object|string  $target
     * @param  string  $property
     * @return static
     */
    public static function onProperty($target, $property)
    {
        return new static(new ReflectionProperty($target, $property));
    }
}
