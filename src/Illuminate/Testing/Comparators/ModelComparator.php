<?php

namespace Illuminate\Testing\Comparators;

use Illuminate\Database\Eloquent\Model;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;

class ModelComparator extends Comparator
{
    /**
     * Checks if the two values are allowed to be compared with this comparator.
     *
     * @param  mixed  $expected
     * @param  mixed  $actual
     * @return bool
     */
    public function accepts($expected, $actual): bool
    {
        return $expected instanceof Model && $actual instanceof Model;
    }

    /**
     * Asserts that expected and actual are the same model.
     *
     * @param \Illuminate\Database\Eloquent\Model $expected
     * @param \Illuminate\Database\Eloquent\Model $actual
     * @param float $delta
     * @param bool $canonicalize
     * @param bool $ignoreCase
     * @return void
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
