<?php

namespace Illuminate\View;

interface StrategyInterface
{
    /**
     * Merge $defaultValue with $value.
     *
     * @param $defaultValue
     * @param $value
     * @return string
     */
    public function __invoke($defaultValue, $value): string;
}
