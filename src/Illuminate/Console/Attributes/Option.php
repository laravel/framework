<?php

namespace Illuminate\Console\Attributes;

use Illuminate\Contracts\Console\ConsoleInput;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Option implements ConsoleInput
{
    public function __construct(
        protected string $description = '',
        protected ?string $as = null,
        protected ?string $shortcut = null,
        protected bool $negatable = false,
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

    public function getShortcut(): ?string
    {
        return $this->shortcut;
    }

    public function isNegatable(): bool
    {
        return $this->negatable;
    }
}
