<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts;

interface HasSolutions
{
    /**
     * @return array<int, \Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\Solution>
     */
    public function getSolutions(): array;
}
