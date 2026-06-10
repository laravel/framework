<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts;

interface RunnableSolution extends Solution
{
    public function command(): string;

    /**
     * @return array<int, string>
     */
    public function commandArguments(): array;
}
