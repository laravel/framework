<?php

namespace Illuminate\Database\Eloquent\Factories;

use Illuminate\Database\Eloquent\Model;

class BelongsToManyRelationship
{
    protected $factory;

    /**
     * The pivot attributes / attribute resolver.
     *
     * @var callable|array
     */
    protected $pivot;

    /**
     * The relationship name.
     *
     * @var string
     */
    protected $relationship;

    public function __construct(Factory $factory, $pivot, $relationship)
    {
        $this->factory = $factory;
        $this->pivot = $pivot;
        $this->relationship = $relationship;
    }

    /**
     * Create the attached relationship for the given model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function createFor(Model $model)
    {
        $model->{$this->relationship}()->attach(
            $this->factory->create([], $model),
            is_callable($this->pivot) ? call_user_func($this->pivot, $model) : $this->pivot
        );
    }
}
