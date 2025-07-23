<?php

namespace Illuminate\Container\Attributes;

use Attribute;
use BackedEnum;
use InvalidArgumentException;
use UnitEnum;

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
     * The environments the binding should apply for.
     *
     * @var non-empty-array<int, string>
     */
    public array $environments = [];

    /**
     * Create a new attribute instance.
     *
     * @param  class-string  $concrete
     * @param  non-empty-array<int, \BackedEnum|\UnitEnum|non-empty-string>|non-empty-string  $environments
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

        $this->environments = array_map(fn ($environment) => match (true) {
            $environment instanceof BackedEnum => $environment->value,
            $environment instanceof UnitEnum => $environment->name,
            default => $environment,
        }, $environments);
    }
}
