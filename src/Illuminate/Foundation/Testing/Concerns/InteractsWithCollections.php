<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Foundation\Testing\TestCollection;

trait InteractsWithCollections
{
    /**
     * Assert that the given value is a collection or a paginator.
     *
     * @param  mixed  $collection
     * @return TestCollection
     */
    protected function assertCollection($collection)
    {
        return new TestCollection($collection);
    }
}
