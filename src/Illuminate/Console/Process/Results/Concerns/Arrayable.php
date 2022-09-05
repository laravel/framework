<?php

namespace Illuminate\Console\Process\Results\Concerns;

use ArrayIterator;
use LogicException;
use Traversable;

/**
 * @mixin \Illuminate\Console\Contracts\ProcessResult
 */
trait Arrayable
{
    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::toArray()}.
     */
    public function toArray()
    {
        return str($this->output())->explode("\n")->toArray();
    }

    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::offsetExists()}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->toArray()[$offset]);
    }

    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::offsetGet()}
     */
    public function offsetGet($offset): mixed
    {
        return $this->toArray()[$offset];
    }

    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::offsetSet()}
     */
    public function offsetSet($offset, $value): void
    {
        throw new LogicException('Process output may not be mutated using array access.');
    }

    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::offsetUnset()}
     */
    public function offsetUnset($offset): void
    {
        throw new LogicException('Process output may not be mutated using array access.');
    }

    /**
     * {@see \Illuminate\Console\Contracts\ProcessResult::getIterator()}
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->toArray());
    }
}
