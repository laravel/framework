<?php

namespace Illuminate\Testing\Fluent\Concerns;

use Closure;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Assert as PHPUnit;

trait Has
{
    protected function count(string $key, $length): self
    {
        PHPUnit::assertCount(
            $length,
            $this->prop($key),
            sprintf('Property [%s] does not have the expected size.', $this->dotPath($key))
        );

        return $this;
    }

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

    public function has(string $key, $value = null, Closure $scope = null): self
    {
        $prop = $this->prop();

        PHPUnit::assertTrue(
            Arr::has($prop, $key),
            sprintf('Property [%s] does not exist.', $this->dotPath($key))
        );

        $this->interactsWith($key);

        // When all three arguments are provided, this indicates a short-hand
        // expression that combines both a `count`-assertion, followed by
        // directly creating a `scope` on the first element.
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

    public function missingAll($key): self
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $prop) {
            $this->missing($prop);
        }

        return $this;
    }

    public function missing(string $key): self
    {
        PHPUnit::assertNotTrue(
            Arr::has($this->prop(), $key),
            sprintf('Property [%s] was found while it was expected to be missing.', $this->dotPath($key))
        );

        return $this;
    }

    abstract protected function prop(string $key = null);

    abstract protected function dotPath($key): string;

    abstract protected function interactsWith(string $key): void;

    abstract protected function scope($key, Closure $callback);
}
