<?php

namespace Illuminate\Database\Eloquent\Factories;

class Sequence
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
    protected $count;

    /**
     * The current index of the sequence.
     *
     * @var int
     */
    protected $index = 0;

    /**
     * The current loop of the sequence.
     *
     * @var int
     */
    protected $loop = 0;

    /**
     * Create a new sequence instance.
     *
     * @param  array  $sequence
     * @return void
     */
    public function __construct(...$sequence)
    {
        $this->sequence = $sequence;
        $this->count = count($sequence);
    }

    /**
     * Get the next value in the sequence.
     *
     * @return mixed
     */
    public function __invoke()
    {
        $this->index = $this->loop % $this->count;

        return tap(value($this->sequence[$this->index], $this->loop, $this->count), function () {
            $this->loop = $this->loop + 1;
        });
    }
}
