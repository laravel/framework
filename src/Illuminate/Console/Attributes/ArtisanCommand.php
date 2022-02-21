<?php

namespace Illuminate\Console\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ArtisanCommand
{
    public function __construct(
        public string $name,
        public string $description = '',
        public string $help = '',
        public array $aliases = [],
        public bool $hidden = false,
    ) {
    }
}
