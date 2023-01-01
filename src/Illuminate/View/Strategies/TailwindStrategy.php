<?php

namespace Illuminate\View\Strategies;

use Illuminate\Support\Str;
use Illuminate\View\StrategyInterface;

class TailwindStrategy implements StrategyInterface
{
    public function __invoke($defaultsValue, $value): string
    {
        return collect(explode(" ", $defaultsValue))
            ->mapWithKeys(fn($v) => [Str::of($v)->match("/.*?\-/")->toString() => $v])
            ->merge(
                collect(explode(" ", $value))
                    ->mapWithKeys(fn($v) => [Str::of($v)->match("/.*?\-/")->toString() => $v])
            )
            ->flatten()
            ->join(' ');
    }
}
