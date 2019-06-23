<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Foundation\Testing\Constraints\HasInCollection;
use Illuminate\Support\Collection;
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
    public function assertCollectionHas(Collection $collection, $item)
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
    public function assertCollectionMissing(Collection $collection, $item)
    {
        $constraint = new ReverseConstraint(
            new HasInCollection($collection)
        );

        $this->assertThat($item, $constraint);

        return $this;
    }
}