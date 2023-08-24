<?php

namespace Illuminate\Foundation\Http\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Ability
{
    /**
     * Name of the ability.
     *
     * @var string|null
     */
    public readonly ?string $ability;

    /**
     * Create a new ability instance.
     *
     * @param  string|null  $ability
     * @return void
     */
    public function __construct(?string $ability)
    {
        $this->ability = $ability;
    }
}
