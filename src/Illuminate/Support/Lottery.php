<?php

namespace Illuminate\Support;

class Lottery
{
    /**
     * @var int
     */
    protected $chances;

    /**
     * @var int
     */
    protected $outOf;

    /**
     * @var null|callable
     */
    protected $winnerCallback;

    /**
     * @var null|callable
     */
    protected $loserCallback;

    /**
     * @param  int  $chances
     * @param  int  $outOf
     */
    public function __construct($chances, $outOf)
    {
        $this->chances = $chances;

        $this->outOf = $outOf;
    }

    /**
     * @param  int  $chances
     * @param  int  $outOf
     * @return static
     */
    public static function odds($chances, $outOf)
    {
        return new static($chances, $outOf);
    }

    /**
     * @param  callable  $callback
     * @return $this
     */
    public function winner($callback)
    {
        $this->winnerCallback = $callback;

        return $this;
    }

    /**
     * @param  callable  $callback
     * @return $this
     */
    public function loser($callback)
    {
        $this->loserCallback = $callback;

        return $this;
    }

    /**
     * @param  mixed  ...$args
     * @return mixed
     */
    public function __invoke(...$args)
    {
        return ($this->pickCallback())(...$args);
    }

    /**
     * @param  null|int  $times
     * @return mixed
     */
    public function choose($times = null)
    {
        if ($times === null) {
            return ($this->pickCallback())();
        }

        $results = [];

        for ($i = 0; $i < $times; $i++) {
            $results[] = ($this->pickCallback())();
        }

        return $results;
    }

    /**
     * @return callable
     */
    protected function pickCallback()
    {
        $callback = $this->wins()
            ? ($this->winnerCallback ?? fn () => true)
            : ($this->loserCallback ?? fn () => false);

        return $callback;
    }

    /**
     * @return bool
     */
    protected function wins()
    {
        return random_int(1, $this->outOf) <= $this->chances;
    }
}
