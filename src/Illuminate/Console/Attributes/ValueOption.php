<?php

namespace Illuminate\Console\Attributes;

use Attribute;
use Closure;
use Symfony\Component\Console\Input\InputOption;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class ValueOption extends Option
{
    /**
     * @param  string  $name
     * @param  bool  $array
     * @param  array|string|null  $shortcut
     * @param  string  $description
     * @param  array|bool|float|int|string|null  $default
     * @param  array|Closure  $suggestedValues
     */
    public function __construct(
        public string $name,
        public bool $array = false,
        public array|string|null $shortcut = null,
        public string $description = '',
        public array|bool|float|int|string|null $default = null,
        public array|Closure $suggestedValues = [],
    ) {
        parent::__construct(
            name: $name,
            shortcut: $shortcut,
            mode: $array ? InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL : InputOption::VALUE_OPTIONAL,
            description: $description,
            default: $default,
            suggestedValues: $suggestedValues,
        );
    }
}
