<?php

namespace Illuminate\Database\Eloquent\Factories;

use Countable;

class Sequence implements Countable
{
    /**
     * The sequence of return values.
     *
     * @var array
     */
    protected $sequence;

    /**
     * The count of the sequence items.
     *
     * @var int
     */
    public $count;

    /**
     * The current index of the sequence iteration.
     *
     * @var int
     */
    public $index = 0;

    /**
     * Create a new sequence instance.
     *
     * @param  mixed  ...$sequence
     */
    public function __construct(...$sequence)
    {
        $this->sequence = $sequence;
        $this->count = count($sequence);
    }

    /**
     * Get the current count of the sequence items.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * Get the next value in the sequence.
     *
     * @return mixed
     */
    public function __invoke()
    {
        return tap(value($this->sequence[$this->index % $this->count], $this), function () {
            $this->index = $this->index + 1;
        });
    }
}
