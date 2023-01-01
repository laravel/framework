<?php

namespace Illuminate\View\Strategies;

use Illuminate\View\StrategyInterface;

class OverrideStrategy implements StrategyInterface
{
    public function __invoke($defaultsValue, $value): string
    {
        return $value;
    }
}
