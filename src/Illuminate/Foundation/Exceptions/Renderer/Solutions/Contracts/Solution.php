<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts;

interface Solution
{
    public function title(): string;

    public function description(): string;

    /**
     * @return array<string, string>
     */
    public function links(): array;
}
