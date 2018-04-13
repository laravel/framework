<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Pagination\Paginator;
use PHPUnit\Framework\Assert as PHPUnit;
use Illuminate\Support\Collection;
use Illuminate\Pagination\AbstractPaginator;

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
