<?php

namespace Illuminate\Container\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Bind
{
    /**
     * The concrete class to bind to.
     *
     * @var class-string
     */
    public string $concrete;

    /**
     * Only use the bindings in this environment.
     *
     * @var non-empty-array<int, string>
     */
    public array $environments = [];

    /**
     * @param  class-string  $concrete
     * @param  non-empty-array<int, non-empty-string>|non-empty-string  $environments
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $concrete,
        string|array $environments = ['*'],
    ) {
        $environments = array_filter(is_array($environments) ? $environments : [$environments]);

        if ($environments === []) {
            throw new InvalidArgumentException('The environment property must be set and cannot be empty.');
        }

        $this->concrete = $concrete;
        $this->environments = $environments;
    }
}
