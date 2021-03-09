<?php

namespace Illuminate\Testing\Fluent\Concerns;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;

trait Matching
{
    /**
     * Asserts that the property matches the expected value.
     *
     * @param  string  $key
     * @param  mixed|\Closure  $expected
     * @return $this
     */
    public function where(string $key, $expected): self
    {
        $this->has($key);

        $actual = $this->prop($key);

        if ($expected instanceof Closure) {
            PHPUnit::assertTrue(
                $expected(is_array($actual) ? Collection::make($actual) : $actual),
                sprintf('Property [%s] was marked as invalid using a closure.', $this->dotPath($key))
            );

            return $this;
        }

        if ($expected instanceof Arrayable) {
            $expected = $expected->toArray();
        }

        $this->ensureSorted($expected);
        $this->ensureSorted($actual);

        PHPUnit::assertSame(
            $expected,
            $actual,
            sprintf('Property [%s] does not match the expected value.', $this->dotPath($key))
        );

        return $this;
    }

    /**
     * Asserts that all properties match their expected values.
     *
     * @param  array  $bindings
     * @return $this
     */
    public function whereAll(array $bindings): self
    {
        foreach ($bindings as $key => $value) {
            $this->where($key, $value);
        }

        return $this;
    }

    /**
     * Ensures that all properties are sorted the same way, recursively.
     *
     * @param  mixed  $value
     * @return void
     */
    protected function ensureSorted(&$value): void
    {
        if (! is_array($value)) {
            return;
        }

        foreach ($value as &$arg) {
            $this->ensureSorted($arg);
        }

        ksort($value);
    }

    /**
     * Compose the absolute "dot" path to the given key.
     *
     * @param  string  $key
     * @return string
     */
    abstract protected function dotPath(string $key): string;

    /**
     * Ensure that the given prop exists.
     *
     * @param  string  $key
     * @param  null  $value
     * @param  \Closure|null  $scope
     * @return $this
     */
    abstract public function has(string $key, $value = null, Closure $scope = null);

    /**
     * Retrieve a prop within the current scope using "dot" notation.
     *
     * @param  string|null  $key
     * @return mixed
     */
    abstract protected function prop(string $key = null);
}
