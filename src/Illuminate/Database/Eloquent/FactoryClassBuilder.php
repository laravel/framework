<?php

namespace Illuminate\Database\Eloquent;

use InvalidArgumentException;

class FactoryClassBuilder extends FactoryBuilder
{
    /**
     * The factory class linked to this builder.
     *
     * @var \Illuminate\Database\Eloquent\FactoryClass
     */
    protected $factoryClass;

    /**
     * Create an new builder instance.
     *
     * @param  \Illuminate\Database\Eloquent\FactoryClass  $factoryClass
     * @return void
     */
    public function __construct(FactoryClass $factoryClass)
    {
        $this->factoryClass = $factoryClass;
        $this->class = $factoryClass->model();
    }

    /**
     * Get a raw attributes array for the model.
     *
     * @param  array  $attributes
     * @return mixed
     */
    protected function getRawAttributes(array $attributes = [])
    {
        return $this->callClosureAttributes(
            array_merge(
                $this->applyStates($this->factoryClass->data($attributes), $attributes),
                $attributes
            )
        );
    }

    /**
     * Make an instance of the model with the given attributes.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function makeInstance(array $attributes = [])
    {
        return Model::unguarded(function () use ($attributes) {
            return new $this->class(
                $this->getRawAttributes($attributes)
            );
        });
    }

    /**
     * Apply the active states to the model definition array.
     *
     * @param  array  $definition
     * @param  array  $attributes
     * @return array
     */
    protected function applyStates(array $definition, array $attributes = [])
    {
        foreach ($this->activeStates as $state) {
            $stateMethod = 'state'.ucfirst($state);

            if (! method_exists($this->factoryClass, $stateMethod)) {
                throw new InvalidArgumentException(
                    "The state method [{$stateMethod}] was not found in the class "
                    .get_class($this->factoryClass)
                );
            }

            $definition = array_merge($definition, $this->factoryClass->$stateMethod($attributes));
        }

        return $definition;
    }
}
