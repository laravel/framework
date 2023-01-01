<?php

namespace Illuminate\View\Strategies;

use Illuminate\View\StrategyInterface;

class AppendStrategy implements StrategyInterface
{
    public function __invoke($defaultsValue, $value): string
    {
        return implode(' ', array_unique(array_filter([$defaultsValue, $value])));
    }
}
