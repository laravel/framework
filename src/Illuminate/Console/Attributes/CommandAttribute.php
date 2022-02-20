<?php

namespace Illuminate\Console\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class CommandAttribute
{
    public function __construct(
        public string $name,
        public string $description = '',
        public string $help = '',
        public bool $hidden = false,
    ) {
    }
}
