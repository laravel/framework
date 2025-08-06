<?php

namespace Illuminate\Console\Attributes;

use Closure;

abstract class Argument
{
    /**
     * @param  string  $name
     * @param  bool  $required
     * @param  bool  $array
     * @param  string  $description
     * @param  array|bool|float|int|string|null  $default
     * @param  array|Closure  $suggestedValues
     */
    public function __construct(
        public string $name,
        public bool $required = true,
        public bool $array = false,
        public string $description = '',
        public array|bool|float|int|string|null $default = null,
        public array|Closure $suggestedValues = [],
    ) {}
}
