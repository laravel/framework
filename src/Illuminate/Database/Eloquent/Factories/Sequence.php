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
     * The parent relationships that will be applied to the model.
     *
     * @var \Illuminate\Support\Collection
     */
    public $has;

    /**
     * Create a new sequence instance.
     *
     * @param  mixed  ...$sequence
     * @return void
     */
    public function __construct(...$sequence)
    {
        $this->sequence = $sequence;
        $this->count = count($sequence);
        $this->has = collect();
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
     * @param Factory $factory The factory that the sequence is invoked from
     *
     * @return mixed
     */
    public function __invoke($factory)
    {
        return tap(value($this->sequence[$this->index % $this->count], $this, $factory), function ($value) {
            if ($value instanceof Factory) {
                $this->has[$this->index] = $value->has;
            }

            $this->index = $this->index + 1;
        });
    }
}
