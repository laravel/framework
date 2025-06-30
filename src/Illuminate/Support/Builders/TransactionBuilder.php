<?php

namespace Illuminate\Support\Builders;
use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Class TransactionBuilder
 *
 * @package \Illuminate\Support\Builders
 */
class TransactionBuilder
{
    /**
     * @var int
     */
    protected int $attempts = 1;

    /**
     * @var string|null
     */
    protected ?string $connection = null;

    /**
     * @var \Closure|null
     */
    protected ?Closure $onSuccess = null;

    /**
     * @var \Closure|null
     */
    protected ?Closure $onFailure = null;

    /**
     * @return static
     */
    public static function start(): static
    {
        return new static();
    }

    /**
     * @param int $times
     *
     * @return $this
     */
    public function attempts(int $times): static
    {
        $this->attempts = $times;
        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function connection(string $name): static
    {
        $this->connection = $name;
        return $this;
    }

    /**
     * @param \Closure $callback
     *
     * @return $this
     */
    public function onSuccess(Closure $callback): static
    {
        $this->onSuccess = $callback;
        return $this;
    }

    /**
     * @param \Closure $callback
     *
     * @return $this
     */
    public function onFailure(Closure $callback): static
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
        $resolver = $this->connection
            ? DB::connection($this->connection)
            : DB::connection();

        try {
            $result = $resolver->transaction($callback, $this->attempts);

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
