<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

trait MorphTypeConstraints
{
    /**
     * The interfaces that related models must implement.
     *
     * @var array
     */
    protected $requiredInterfaces = [];

    /**
     * The abstract classes that related models must extend.
     *
     * @var array
     */
    protected $requiredAbstractClasses = [];

    /**
     * Require that all morph-related models implement a specific interface.
     *
     * @param  string|array  $interface
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function mustImplement($interface)
    {
        $interfaces = is_array($interface) ? $interface : [$interface];

        foreach ($interfaces as $interface) {
            if (! interface_exists($interface)) {
                throw new \InvalidArgumentException("Interface [{$interface}] does not exist.");
            }

            $this->requiredInterfaces[] = $interface;
        }

        return $this;
    }

    /**
     * Require that all morph-related models extend a specific abstract class.
     *
     * @param  string|array  $abstractClass
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function mustExtend($abstractClass)
    {
        $classes = is_array($abstractClass) ? $abstractClass : [$abstractClass];

        foreach ($classes as $class) {
            if (! class_exists($class)) {
                throw new \InvalidArgumentException("Class [{$class}] does not exist.");
            }

            if (! (new \ReflectionClass($class))->isAbstract()) {
                throw new \InvalidArgumentException("Class [{$class}] is not abstract.");
            }

            $this->requiredAbstractClasses[] = $class;
        }

        return $this;
    }

    /**
     * Validate that the related model meets all type constraints.
     *
     * @param  mixed  $model
     * @return void
     *
     * @throws \RuntimeException
     */
    protected function validateRelatedModel($model)
    {
        if ($model === null) {
            return;
        }

        foreach ($this->requiredInterfaces as $interface) {
            if (! ($model instanceof $interface)) {
                throw new \RuntimeException(
                    sprintf(
                        'Related model [%s] must implement interface [%s].',
                        get_class($model),
                        $interface
                    )
                );
            }
        }

        foreach ($this->requiredAbstractClasses as $abstractClass) {
            if (! is_a($model, $abstractClass)) {
                throw new \RuntimeException(
                    sprintf(
                        'Related model [%s] must extend abstract class [%s].',
                        get_class($model),
                        $abstractClass
                    )
                );
            }
        }
    }

    /**
     * Validate all models in a collection meet the type constraints.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    protected function validateRelatedCollection($models)
    {
        foreach ($models as $model) {
            $this->validateRelatedModel($model);
        }
    }
}
