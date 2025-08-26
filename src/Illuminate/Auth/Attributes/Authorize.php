<?php

namespace Illuminate\Auth\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Authorize
{
    /**
     * The ability to authorize.
     *
     * @var string
     */
    public string $ability;

    /**
     * The models to pass to the gate.
     *
     * @var array
     */
    public array $models;

    /**
     * Create a new authorize attribute instance.
     *
     * @param  string  $ability
     * @param  string|array  ...$models
     */
    public function __construct(string $ability, string|array ...$models)
    {
        $this->ability = $ability;
        $this->models = is_array($models[0] ?? null) ? $models[0] : $models;
    }
}
