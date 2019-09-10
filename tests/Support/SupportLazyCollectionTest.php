<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\TestCase;

class SupportLazyCollectionTest extends TestCase
{
    public function testCanCreateEmptyCollection()
    {
        $this->assertSame([], LazyCollection::make()->all());
        $this->assertSame([], LazyCollection::empty()->all());
    }

    public function testCanCreateCollectionFromArray()
    {
        $array = [1, 2, 3];

        $data = LazyCollection::make($array);

        $this->assertSame($array, $data->all());

        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $data = LazyCollection::make($array);

        $this->assertSame($array, $data->all());
    }

    public function testCanCreateCollectionFromArrayable()
    {
        $array = [1, 2, 3];

        $data = LazyCollection::make(Collection::make($array));

        $this->assertSame($array, $data->all());

        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $data = LazyCollection::make(Collection::make($array));

        $this->assertSame($array, $data->all());
    }

    public function testCanCreateCollectionFromClosure()
    {
        $data = LazyCollection::make(function () {
            yield 1;
            yield 2;
            yield 3;
        });

        $this->assertSame([1, 2, 3], $data->all());

        $data = LazyCollection::make(function () {
            yield 'a' => 1;
            yield 'b' => 2;
            yield 'c' => 3;
        });

        $this->assertSame([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ], $data->all());
    }

    public function testTapEach()
    {
        $data = LazyCollection::times(10);

        $tapped = [];

        $data = $data->tapEach(function ($value, $key) use (&$tapped) {
            $tapped[$key] = $value;
        });

        $this->assertEmpty($tapped);

        $data = $data->take(5)->all();

        $this->assertSame([1, 2, 3, 4, 5], $data);
        $this->assertSame([1, 2, 3, 4, 5], $tapped);
    }
}
