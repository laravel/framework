<?php

namespace Illuminate\Testing\Comparators;

use Illuminate\Database\Eloquent\Model;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;

class ModelComparator extends Comparator
{
    public function accepts($expected, $actual): bool
    {
        return $expected instanceof Model && $actual instanceof Model;
    }

    /**
     * @param Model $expected
     * @param Model $actual
     */
    public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = false, $ignoreCase = false): void
    {
        if (! $expected->is($actual)) {
            throw new ComparisonFailure(
                $expected,
                $actual,
                "{$expected->getMorphClass()}::{$expected->getKey()}",
                "{$actual->getMorphClass()}::{$actual->getKey()}",
            );
        }
    }
}
