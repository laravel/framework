<?php

namespace Illuminate\Support\EnvProcessors;

interface EnvProcessorInterface
{
    /**
     * @param  string  $value
     * @return mixed
     */
    public function __invoke(string $value): mixed;
}
