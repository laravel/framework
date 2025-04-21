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
    protected $requiredClasses = [];

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
        $this->requiredInterfaces[] = $interface;
        return $this;
    }

    /**
     * Require that all morph-related models extend a specific abstract class.
     *
     * @param  string|array  $class
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function mustExtend($class)
    {
        $this->requiredClasses[] = $class;
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

        foreach ($this->requiredClasses as $class) {
            if (! is_a($model, $class)) {
                throw new \RuntimeException(
                    sprintf(
                        'Related model [%s] must extend class [%s].',
                        get_class($model),
                        $class
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
