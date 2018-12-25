<?php

namespace Illuminate\Container;

use Countable;
use IteratorAggregate;

class RewindableGenerator implements Countable, IteratorAggregate
{
    /**
     * @var callable
     */
    private $generator;

    /**
     * @var callable|int
     */
    private $count;

    /**
     * @param callable     $generator
     * @param callable|int $count
     */
    public function __construct(callable $generator, $count)
    {
        $this->generator = $generator;
        $this->count = $count;
    }

    public function getIterator()
    {
        $generator = $this->generator;

        return $generator();
    }

    /**
     * @return int
     */
    public function count()
    {
        if (is_callable($count = $this->count)) {
            $this->count = $count();
        }

        return $this->count;
    }
}
