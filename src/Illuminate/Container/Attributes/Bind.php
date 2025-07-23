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
     * @var array<int, string>
     */
    public array $environments = [];

    /**
     * @param  class-string  $concrete
     * @param  array<int, string>|string  $environments
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        string $concrete,
        string|array $environments = ['*'],
    ) {
        $environments = is_array($environments) ? $environments : [$environments];

        if ($environments === []) {
            throw new InvalidArgumentException('The environment property must be set and cannot be empty.');
        }

        $this->concrete = $concrete;
        $this->environments = $environments;
    }
}
