<?php

namespace Illuminate\Support;

class Lottery
{
    /**
     * The number of expected wins.
     *
     * @var int
     */
    protected $chances;

    /**
     * The number of potential opportunities to win.
     *
     * @var int
     */
    protected $outOf;

    /**
     * The winning callback.
     *
     * @var null|callable
     */
    protected $winner;

    /**
     * The losing callback.
     *
     * @var null|callable
     */
    protected $loser;

    /**
     * Create a new Lottery instance.
     *
     * @param  int  $chances
     * @param  int  $outOf
     */
    public function __construct($chances, $outOf)
    {
        $this->chances = $chances;

        $this->outOf = $outOf;
    }

    /**
     * Create a new Lottery instance.
     *
     * @param  int  $chances
     * @param  int  $outOf
     * @return static
     */
    public static function odds($chances, $outOf)
    {
        return new static($chances, $outOf);
    }

    /**
     * Set the winner callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function winner($callback)
    {
        $this->winner = $callback;

        return $this;
    }

    /**
     * Set the loser callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function loser($callback)
    {
        $this->loser = $callback;

        return $this;
    }

    /**
     * Run the lottery.
     *
     * @param  mixed  ...$args
     * @return mixed
     */
    public function __invoke(...$args)
    {
        return $this->runCallback(...$args);
    }

    /**
     * Run the lottery.
     *
     * @param  null|int  $times
     * @return mixed
     */
    public function choose($times = null)
    {
        if ($times === null) {
            return $this->runCallback();
        }

        $results = [];

        for ($i = 0; $i < $times; $i++) {
            $results[] = $this->runCallback();
        }

        return $results;
    }

    /**
     * Run the winner or loser callback, randomly.
     *
     * @param  mixed  ...$args
     * @return callable
     */
    protected function runCallback(...$args)
    {
        return $this->wins()
            ? ($this->winner ?? fn () => true)(...$args)
            : ($this->loser ?? fn () => false)(...$args);
    }

    /**
     * Determine if the lottery "wins" or "loses".
     *
     * @return bool
     */
    protected function wins()
    {
        return random_int(1, $this->outOf) <= $this->chances;
    }
}
