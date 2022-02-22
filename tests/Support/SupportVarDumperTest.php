<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\VarDumper;
use PHPUnit\Framework\TestCase;

class SupportVarDumperTest extends TestCase
{
    public function test_ddt_works()
    {
        VarDumper::fake();

        $array = ['a', 'b', 'c', 'd', 'e'];

        foreach ($array as $item) {
            ddt(3, $item);
        }

        $this->assertEquals(VarDumper::getDumpedCount(), 3);
        $this->assertTrue(VarDumper::died());
        $this->assertEquals(['a', 'b', 'c'], VarDumper::getDumpedItems());

        VarDumper::reset();

        VarDumper::fake();

        $array = ['a', 'b', 'c', 'd', 'e'];

        foreach ($array as $item) {
            ddt(4, $item, 'test');
        }

        $this->assertEquals(VarDumper::getDumpedCount(), 4);
        $this->assertTrue(VarDumper::died());
        $this->assertEquals(['a', 'test', 'b', 'test', 'c', 'test', 'd', 'test'], VarDumper::getDumpedItems());

        VarDumper::reset();
    }

    public function test_works_with_count_of_array()
    {
        VarDumper::fake();
        $array = ['a', 'b', 'c', 'd', 'e'];

        foreach ($array as $item) {
            ddt(count($array), $item);
        }

        $this->assertEquals(VarDumper::getDumpedCount(), count($array));
        $this->assertTrue(VarDumper::died());
        $this->assertEquals($array, VarDumper::getDumpedItems());

        VarDumper::reset();
    }

    public function test_zero_times_wont_dump_anything_and_just_dies()
    {
        VarDumper::fake();

        $array = ['a', 'b', 'c', 'd', 'e'];

        foreach ($array as $item) {
            ddt(0, $item);
        }

        $this->assertEquals(VarDumper::getDumpedCount(), 0);
        $this->assertTrue(VarDumper::died());
        $this->assertEquals([], VarDumper::getDumpedItems());

        VarDumper::reset();
    }
}
