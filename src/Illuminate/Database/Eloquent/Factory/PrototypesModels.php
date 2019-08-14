<?php

namespace Illuminate\Database\Eloquent\Factory;

trait PrototypesModels
{
    /**
     * Name of the definition.
     *
     * @var string
     */
    public $definition;

    /**
     * The states to apply.
     *
     * @var array
     */
    public $states = [];

    /**
     * The presets to apply.
     *
     * @var array
     */
    public $presets = [];

    /**
     * The number of models to build.
     *
     * @var int|null
     */
    public $amount;

    /**
     * Attributes to apply.
     *
     * @var array
     */
    public $attributes = [];

    /**
     * Attributes to apply to a pivot relation.
     *
     * @var array
     */
    public $pivotAttributes = [];
}
