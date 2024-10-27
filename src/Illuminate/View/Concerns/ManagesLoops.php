<?php

namespace Illuminate\View\Concerns;

use Illuminate\Support\LazyCollection;
use StdClass;

trait ManagesLoops
{
    /**
     * The stack of in-progress loops.
     *
     * @var array
     */
    protected array $loopsStack = [];

    /**
     * Add new loop to the stack.
     *
     * @template TData of \Countable|array
     *
     * @param  TData  $data
     * @return TData
     */
    public function addLoop(mixed $data): mixed
    {
        $length = is_countable($data) && ! $data instanceof LazyCollection
                            ? count($data)
                            : null;

        $this->loopsStack[] = [
            'iteration' => 1,
            'index' => 0,
            'remaining' => isset($length) ? $length - 1 : null,
            'count' => $length,
            'first' => true,
            'last' => isset($length) ? $length === 1 : null,
            'odd' => true,
            'even' => false,
            'depth' => count($this->loopsStack) + 1,
            'parent' => $this->getLastLoop(),
        ];

        return $data;
    }

    /**
     * Increment the top loop's indices.
     *
     * @return void
     */
    public function incrementLoopIndices(): void
    {
        $loop = $this->loopsStack[$index = count($this->loopsStack) - 1];

        $this->loopsStack[$index] = [
            'iteration' => $loop['iteration'] + 1,
            'index' => $loop['iteration'],
            'remaining' => isset($loop['count']) ? $loop['remaining'] - 1 : null,
            'count' => $loop['count'],
            'first' => $loop['iteration'] === 0,
            'last' => isset($loop['count']) ? $loop['iteration'] == $loop['count'] - 1 : null,
            'odd' => ! $loop['odd'],
            'even' => ! $loop['even'],
            'depth' => $loop['depth'],
            'parent' => $loop['parent'],
        ];
    }

    /**
     * Pop a loop from the top of the loop stack and return the last loop in the stack.
     *
     * @return \stdClass|null
     */
    public function popLoop(): ?StdClass
    {
        array_pop($this->loopsStack);

        return $this->getLastLoop();
    }

    /**
     * Get an instance of the last loop in the stack.
     *
     * @return \stdClass|null
     */
    public function getLastLoop(): ?StdClass
    {
        return ($loop = end($this->loopsStack)) ? (object) $loop : null;
    }

    /**
     * Get the entire loop stack.
     *
     * @return array
     */
    public function getLoopStack(): array
    {
        return $this->loopsStack;
    }
}
