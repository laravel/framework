<?php

namespace Illuminate\Tests\Pagination;

use PHPUnit\Framework\TestCase;
use Illuminate\Pagination\UrlWindow;
use Illuminate\Pagination\LengthAwarePaginator;

class UrlWindowTest extends TestCase
{
    public function testPresenterCanDetermineIfThereAreAnyPagesToShow()
    {
        $p = new LengthAwarePaginator($array = ['item1', 'item2', 'item3', 'item4'], 4, 2, 2);
        $window = new UrlWindow($p);
        $this->assertTrue($window->hasPages());
    }

    public function testPresenterCanGetAUrlRangeForASmallNumberOfUrls()
    {
        $p = new LengthAwarePaginator($array = ['item1', 'item2', 'item3', 'item4'], 4, 2, 2);
        $window = new UrlWindow($p);
        $this->assertEquals(['first' => [1 => '/?page=1', 2 => '/?page=2'], 'slider' => null, 'last' => null], $window->get());
    }

    public function testPresenterCanGetAUrlRangeForAWindowOfLinks()
    {
        $array = [];
        for ($i = 1; $i <= 13; $i++) {
            $array[$i] = 'item'.$i;
        }
        $p = new LengthAwarePaginator($array, count($array), 1, 7);
        $window = new UrlWindow($p);
        $slider = [];
        for ($i = 4; $i <= 10; $i++) {
            $slider[$i] = '/?page='.$i;
        }

        $this->assertEquals(['first' => [1 => '/?page=1', 2 => '/?page=2'], 'slider' => $slider, 'last' => [12 => '/?page=12', 13 => '/?page=13']], $window->get());

        /*
         * Test Being Near The End Of The List
         */
        $p = new LengthAwarePaginator($array, count($array), 1, 8);
        $window = new UrlWindow($p);
        $last = [];
        for ($i = 5; $i <= 13; $i++) {
            $last[$i] = '/?page='.$i;
        }

        $this->assertEquals(['first' => [1 => '/?page=1', 2 => '/?page=2'], 'slider' => null, 'last' => $last], $window->get());
    }
}
