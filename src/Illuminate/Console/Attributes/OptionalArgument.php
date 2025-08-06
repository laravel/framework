<?php

namespace Illuminate\Console\Attributes;

use Attribute;
use Closure;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class OptionalArgument extends Argument
{
    /**
     * @param  string  $name
     * @param  bool  $array
     * @param  string  $description
     * @param  array|bool|float|int|string|null  $default
     * @param  array|Closure  $suggestedValues
     */
    public function __construct(
        public string $name,
        public bool $array = false,
        public string $description = '',
        public array|bool|float|int|string|null $default = null,
        public array|Closure $suggestedValues = [],
    ) {
        parent::__construct(
            name: $name,
            required: false,
            array: $array,
            description: $description,
            default: $default,
            suggestedValues: $suggestedValues,
        );
    }
}
