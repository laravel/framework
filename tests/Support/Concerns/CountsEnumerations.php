<?php

namespace Illuminate\Tests\Support\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

trait CountsEnumerations
{
    protected function makeGeneratorFunctionWithRecorder($numbers = 10)
    {
        $recorder = new Collection();

        $generatorFunction = function () use ($numbers, $recorder) {
            for ($i = 1; $i <= $numbers; $i++) {
                $recorder->push($i);

                yield $i;
            }
        };

        return [$generatorFunction, $recorder];
    }

    protected function assertDoesNotEnumerate(callable $executor)
    {
        $this->assertEnumerates(0, $executor);
    }

    protected function assertDoesNotEnumerateCollection(
        LazyCollection $collection,
        callable $executor
    ) {
        $this->assertEnumeratesCollection($collection, 0, $executor);
    }

    protected function assertEnumerates($count, callable $executor)
    {
        $this->assertEnumeratesCollection(
            LazyCollection::times(100),
            $count,
            $executor
        );
    }

    protected function assertEnumeratesCollection(
        LazyCollection $collection,
        $count,
        callable $executor
    ) {
        $enumerated = 0;

        $data = $this->countEnumerations($collection, $enumerated);

        $executor($data);

        $this->assertEnumerations($count, $enumerated);
    }

    protected function assertEnumeratesOnce(callable $executor)
    {
        $this->assertEnumeratesCollectionOnce(LazyCollection::times(10), $executor);
    }

    protected function assertEnumeratesCollectionOnce(
        LazyCollection $collection,
        callable $executor
    ) {
        $enumerated = 0;
        $count = $collection->count();
        $collection = $this->countEnumerations($collection, $enumerated);

        $executor($collection);

        $this->assertEquals(
            $count,
            $enumerated,
            $count > $enumerated ? 'Failed to enumerate in full.' : 'Enumerated more than once.'
        );
    }

    protected function assertEnumerations($expected, $actual)
    {
        $this->assertEquals(
            $expected,
            $actual,
            "Failed asserting that {$actual} items that were enumerated matches expected {$expected}."
        );
    }

    protected function countEnumerations(LazyCollection $collection, &$count)
    {
        return $collection->tapEach(function () use (&$count) {
            $count++;
        });
    }
}
