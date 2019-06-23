<?php

namespace Illuminate\Foundation\Testing\Constraints;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Constraint\Constraint;

class HasInCollection extends Constraint
{
    /**
     * @var Collection
     */
    private $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    protected function matches($item): bool
    {
        return $this->collection->contains($item);
    }

    /**
     * Get the description of the failure.
     *
     * @param  mixed  $item
     * @return string
     */
    public function failureDescription($item): string
    {
        return sprintf(
            "the collection contains the item %s.\n\n%s",
            $this->itemToString($item, JSON_PRETTY_PRINT),
            $this->toString(JSON_PRETTY_PRINT)
        );
    }

    private function itemToString($item, $options = 0): string
    {
        return json_encode($item, $options);
    }

    /**
     * Returns a string representation of the object.
     *
     * @param  int  $options
     * @return string
     */
    public function toString($options = 0): string
    {
        return $this->collection->toJson($options);
    }
}
