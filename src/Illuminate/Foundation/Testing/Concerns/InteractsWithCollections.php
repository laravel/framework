<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\Constraints\HasInCollection;
use PHPUnit\Framework\Constraint\LogicalNot as ReverseConstraint;

trait InteractsWithCollections
{
    /**
     * Assert that a given item exists in the collection.
     *
     * @param  Collection  $collection
     * @param  mixed  $item
     * @return $this
     */
    protected function assertCollectionHas(Collection $collection, $item)
    {
        $this->assertThat(
            $item, new HasInCollection($collection)
        );

        return $this;
    }

    /**
     * Assert that a given item does not exist in the collection.
     *
     * @param  Collection  $collection
     * @param  mixed  $item
     * @return $this
     */
    protected function assertCollectionMissing(Collection $collection, $item)
    {
        $constraint = new ReverseConstraint(
            new HasInCollection($collection)
        );

        $this->assertThat($item, $constraint);

        return $this;
    }

    /**
     * Assert that a given item exists in the collection (using strict comparison).
     *
     * @param  Collection  $collection
     * @param  mixed  $item
     * @return $this
     */
    protected function assertCollectionHasStrict(Collection $collection, $item)
    {
        $this->assertThat(
            $item, new HasInCollection($collection, true)
        );

        return $this;
    }

    /**
     * Assert that a given item does not exist in the collection (using strict comparison).
     *
     * @param  Collection  $collection
     * @param  mixed  $item
     * @return $this
     */
    protected function assertCollectionMissingStrict(Collection $collection, $item)
    {
        $constraint = new ReverseConstraint(
            new HasInCollection($collection, true)
        );

        $this->assertThat($item, $constraint);

        return $this;
    }
}
