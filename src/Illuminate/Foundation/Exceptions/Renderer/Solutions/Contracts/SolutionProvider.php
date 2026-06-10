<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts;

use Throwable;

interface SolutionProvider
{
    public function canSolve(Throwable $throwable): bool;

    /**
     * @return array<int, \Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\Solution>
     */
    public function getSolutions(Throwable $throwable): array;
}
