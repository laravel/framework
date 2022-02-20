<?php

namespace Illuminate\Console\Attributes;

use Illuminate\Contracts\Console\ConsoleInput;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Argument implements ConsoleInput
{
    public function __construct(
        protected string $description = '',
        protected ?string $as = null,
    ) {
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAlias(): ?string
    {
        return $this->as;
    }
}
