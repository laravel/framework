<?php

namespace Illuminate\Foundation\Testing;

use PHPUnit\Framework\Assert;

class TestCollectionData extends TestData
{
    public function contains($key)
    {
        Assert::assertTrue($this->data->contains($key));

        return $this;
    }

    public function notContains($key)
    {
        Assert::assertFalse($this->data->contains($key));

        return $this;
    }
}
