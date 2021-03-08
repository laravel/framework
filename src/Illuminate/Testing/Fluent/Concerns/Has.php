<?php

namespace Illuminate\Testing\Fluent\Concerns;

use Closure;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Assert as PHPUnit;

trait Has
{
    /**
     * Assert that the prop is of the expected size.
     *
     * @param  string  $key
     * @param  int  $length
     * @return $this
     */
    protected function count(string $key, int $length): self
    {
        PHPUnit::assertCount(
            $length,
            $this->prop($key),
            sprintf('Property [%s] does not have the expected size.', $this->dotPath($key))
        );

        return $this;
    }

    /**
     * Ensure that the given prop exists.
     *
     * @param  string  $key
     * @param  null  $value
     * @param  \Closure|null  $scope
     * @return $this
     */
    public function has(string $key, $value = null, Closure $scope = null): self
    {
        $prop = $this->prop();

        PHPUnit::assertTrue(
            Arr::has($prop, $key),
            sprintf('Property [%s] does not exist.', $this->dotPath($key))
        );

        $this->interactsWith($key);

        // When all three arguments are provided this indicates a short-hand expression
        // that combines both a `count`-assertion, followed by directly creating the
        // `scope` on the first element. We can simply handle this correctly here.
        if (is_int($value) && ! is_null($scope)) {
            $prop = $this->prop($key);
            $path = $this->dotPath($key);

            PHPUnit::assertTrue($value > 0, sprintf('Cannot scope directly onto the first entry of property [%s] when asserting that it has a size of 0.', $path));
            PHPUnit::assertIsArray($prop, sprintf('Direct scoping is unsupported for non-array like properties such as [%s].', $path));

            $this->count($key, $value);

            return $this->scope($key.'.'.array_keys($prop)[0], $scope);
        }

        if (is_callable($value)) {
            $this->scope($key, $value);
        } elseif (! is_null($value)) {
            $this->count($key, $value);
        }

        return $this;
    }

    /**
     * Assert that all of the given props exist.
     *
     * @param  array|string $key
     * @return $this
     */
    public function hasAll($key): self
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $prop => $count) {
            if (is_int($prop)) {
                $this->has($count);
            } else {
                $this->has($prop, $count);
            }
        }

        return $this;
    }

    /**
     * Assert that none of the given props exist.
     *
     * @param  array|string $key
     * @return $this
     */
    public function missingAll($key): self
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $prop) {
            $this->missing($prop);
        }

        return $this;
    }

    /**
     * Assert that the given prop does not exist.
     *
     * @param  string  $key
     * @return $this
     */
    public function missing(string $key): self
    {
        PHPUnit::assertNotTrue(
            Arr::has($this->prop(), $key),
            sprintf('Property [%s] was found while it was expected to be missing.', $this->dotPath($key))
        );

        return $this;
    }

    /**
     * Compose the absolute "dot" path to the given key.
     *
     * @param  string  $key
     * @return string
     */
    abstract protected function dotPath(string $key): string;

    /**
     * Marks the property as interacted.
     *
     * @param  string  $key
     * @return void
     */
    abstract protected function interactsWith(string $key): void;

    /**
     * Retrieve a prop within the current scope using "dot" notation.
     *
     * @param  string|null  $key
     * @return mixed
     */
    abstract protected function prop(string $key = null);

    /**
     * Instantiate a new "scope" at the path of the given key.
     *
     * @param  string  $key
     * @param  \Closure  $callback
     * @return $this
     */
    abstract protected function scope(string $key, Closure $callback);
}
