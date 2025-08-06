<?php

namespace Illuminate\Console\Attributes;

use Closure;
use Symfony\Component\Console\Input\InputOption;

abstract class Option
{
    /**
     * @param  string  $name
     * @param  array|string|null  $shortcut
     * @param  int  $mode
     * @param  string  $description
     * @param  array|bool|float|int|string|null  $default
     * @param  array|Closure  $suggestedValues
     */
    public function __construct(
        public string $name,
        public array|string|null $shortcut = null,
        public int $mode = InputOption::VALUE_NONE,
        public string $description = '',
        public array|bool|float|int|string|null $default = null,
        public array|Closure $suggestedValues = [],
    ) {
    }
}
