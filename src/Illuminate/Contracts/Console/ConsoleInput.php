<?php

namespace Illuminate\Contracts\Console;

interface ConsoleInput
{
    public function getDescription(): string;

    public function getAlias(): ?string;
}
