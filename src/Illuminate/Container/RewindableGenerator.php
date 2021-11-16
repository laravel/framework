<?php

namespace Illuminate\Container;

use Countable;
use IteratorAggregate;

class RewindableGenerator implements Countable, IteratorAggregate
{
    /**
     * The generator callback.
     *
     * @var callable
     */
    protected $generator;

    /**
     * The number of tagged services.
     *
     * @var callable|int
     */
    protected $count;

    /**
     * Create a new generator instance.
     *
     * @param  callable  $generator
     * @param  callable|int  $count
     * @return void
     */
    public function __construct(callable $generator, $count)
    {
        $this->count = $count;
        $this->generator = $generator;
    }

    /**
     * Get an iterator from the generator.
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return ($this->generator)();
    }

    /**
     * Get the total number of tagged services.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        if (is_callable($count = $this->count)) {
            $this->count = $count();
        }

        return $this->count;
    }
}
