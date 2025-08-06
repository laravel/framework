<?php

namespace Illuminate\Console\Attributes;

use Attribute;
use Closure;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class RequiredArgument extends Argument
{
    /**
     * @param  string  $name
     * @param  bool  $array
     * @param  string  $description
     * @param  array|Closure  $suggestedValues
     */
    public function __construct(
        public string $name,
        public bool $array = false,
        public string $description = '',
        public array|Closure $suggestedValues = [],
    ) {
        parent::__construct(
            name: $name,
            array: $array,
            description: $description,
            suggestedValues: $suggestedValues,
        );
    }
}
