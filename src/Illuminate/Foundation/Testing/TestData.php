<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Pagination\Paginator;
use PHPUnit\Framework\Assert;
use Illuminate\Support\Collection;
use Illuminate\Pagination\AbstractPaginator;

class TestData
{
    protected $data;
    protected $response;

    public function __construct($data, $response)
    {
        $this->data = $data;
        $this->response = $response;
    }

    public static function make($data, $response)
    {
        if ($data instanceof Collection || $data instanceof Paginator) {
            return new TestCollectionData($data, $response);
        }

        return new static($data, $response);
    }

    public function contains($key)
    {
        Assert::assertContains($key, $this->data);

        return $this;
    }

    public function notContains($key)
    {
        Assert::assertNotContains($key, $this->data);

        return $this;
    }

    public function __call($method, $arguments)
    {
        return $this->response->$method(...$arguments);
    }
}
