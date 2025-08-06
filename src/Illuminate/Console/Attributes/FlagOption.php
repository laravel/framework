<?php

namespace Illuminate\Console\Attributes;

use Attribute;
use Symfony\Component\Console\Input\InputOption;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class FlagOption extends Option
{
    /**
     * @param  string  $name
     * @param  array|string|null  $shortcut
     * @param  string  $description
     * @param  bool  $negatable
     */
    public function __construct(
        public string $name,
        public array|string|null $shortcut = null,
        public string $description = '',
        public bool $negatable = false,
    ) {
        parent::__construct(
            name: $name,
            shortcut: $shortcut,
            mode: $negatable ? InputOption::VALUE_NEGATABLE | InputOption::VALUE_NONE : InputOption::VALUE_NONE,
            description: $description,
        );
    }
}
