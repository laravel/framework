<?php

namespace Illuminate\Testing\Fluent\Concerns;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;

trait Matching
{
    public function whereAll(array $bindings): self
    {
        foreach ($bindings as $key => $value) {
            $this->where($key, $value);
        }

        return $this;
    }

    public function where($key, $expected): self
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

    abstract protected function dotPath($key): string;

    abstract protected function prop(string $key = null);

    abstract public function has(string $key, $value = null, Closure $scope = null);
}
