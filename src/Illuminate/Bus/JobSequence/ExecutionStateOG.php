<?php

namespace Illuminate\Bus\JobSequence;

use Exception;
use Illuminate\Support\Str;

class ExecutionStateOG
{
    protected string $_id;

    /**
     * @var array<array-key, mixed>
     */
    protected $data = [];

    public function __construct(?string $id = null)
    {
        $this->_id = $id ?? Str::random(32);
    }

    public function id(): string
    {
        return $this->_id;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * @param  string  $key
     * @return mixed
     * @throws Exception
     */
    public function __get(string $key): mixed
    {
        if (! array_key_exists($key, $this->data)) {
            throw new Exception("Property [{$key}] does not exist on the ExecutionState.");
        }

        return $this->data[$key];
    }

    public function __set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
}
