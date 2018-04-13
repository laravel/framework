<?php

namespace Illuminate\Foundation\Testing;

use PHPUnit\Framework\Assert as PHPUnit;

class TestView
{
    protected $view;
    protected $response;

    public function __construct($response)
    {
        $this->response = $response;
        $this->view = $response->original;
    }

    /**
     * Assert that the response view equals the given value.
     *
     * @param  string $value
     * @return $this
     */
    public function is($value)
    {
        PHPUnit::assertEquals($value, $this->view->getName());

        return $this;
    }

    /**
     * Assert that the response view has a given piece of bound data.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function has($key, $value = null)
    {
        if (is_array($key)) {
            return $this->hasAll($key);
        }

        if (is_null($value)) {
            PHPUnit::assertArrayHasKey($key, $this->view->getData());
        } elseif ($value instanceof Closure) {
            PHPUnit::assertTrue($value($this->view->$key));
        } else {
            PHPUnit::assertEquals($value, $this->view->$key);
        }

        return TestData::make($this->view->$key, $this);
    }

    /**
     * Assert that the response view has a given list of bound data.
     *
     * @param  array  $bindings
     * @return $this
     */
    public function hasAll(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                $this->assertViewHas($value);
            } else {
                $this->assertViewHas($key, $value);
            }
        }

        return $this;
    }

    public function __call($method, $arguments)
    {
        return $this->response->$method(...$arguments);
    }
}
