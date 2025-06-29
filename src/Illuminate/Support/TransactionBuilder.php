<?php

namespace Illuminate\Support;


use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionBuilder
{
    /**
     * @var int
     */
    protected int $attempts = 1;
    /**
     * @var Closure|null
     */
    protected ?Closure $onSuccess = null;
    /**
     * @var \Closure|null
     */
    protected ?Closure $onFailure = null;

    /**
     * @return static
     */
    public static function start(): self
    {
        return new self();
    }

    /**
     * @param int $times
     *
     * @return $this
     */
    public function attempts(int $times): self
    {
        $this->attempts = $times;
        return $this;
    }

    /**
     * @param \Closure $callback
     *
     * @return $this
     */
    public function onSuccess(Closure $callback): self
    {
        $this->onSuccess = $callback;
        return $this;
    }

    /**
     * @param \Closure $callback
     *
     * @return $this
     */
    public function onFailure(Closure $callback): self
    {
        $this->onFailure = $callback;
        return $this;
    }

    /**
     * @param \Closure $callback
     *
     * @return mixed
     * @throws \Throwable
     */
    public function run(Closure $callback): mixed
    {
        try {
            $result = DB::transaction($callback, $this->attempts);

            if ($this->onSuccess)
                ($this->onSuccess)($result);

            return $result;

        } catch (Throwable $e) {

            if ($this->onFailure)
                ($this->onFailure)($e);

            throw $e;
        }
    }
}
