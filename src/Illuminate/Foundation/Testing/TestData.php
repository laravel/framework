<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Contracts\Pagination\Paginator;

class TestData
{
    protected $data;
    protected $fallback;

    public function __construct($data, $fallback)
    {
        $this->data = $data;
        $this->fallback = $fallback;
    }

    public static function make($data, $fallback)
    {
        if ($data instanceof Collection || $data instanceof Paginator) {
            return new TestCollectionData($data, $fallback);
        }

        return new static($data, $fallback);
    }

    public function instanceOf($className)
    {
        PHPUnit::assertInstanceOf($className, $this->data);

        return $this;
    }

    public function with($attribute, $value)
    {
        PHPUnit::assertSame(
            $value, $this->data->$attribute ?? null
        );

        return $this;
    }

    public function contains($key)
    {
        PHPUnit::assertContains($key, $this->data);

        return $this;
    }

    public function notContains($key)
    {
        PHPUnit::assertNotContains($key, $this->data);

        return $this;
    }

    public function __call($method, $arguments)
    {
        return $this->fallback->$method(...$arguments);
    }
}
