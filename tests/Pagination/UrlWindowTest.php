<?php

namespace Illuminate\Tests\Pagination;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\UrlWindow;
use PHPUnit\Framework\TestCase;

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
        for ($i = 1; $i <= 20; $i++) {
            $array[$i] = 'item'.$i;
        }
        $p = new LengthAwarePaginator($array, count($array), 1, 12);
        $window = new UrlWindow($p);
        $slider = [];
        for ($i = 9; $i <= 15; $i++) {
            $slider[$i] = '/?page='.$i;
        }

        $this->assertEquals(['first' => [1 => '/?page=1', 2 => '/?page=2'], 'slider' => $slider, 'last' => [19 => '/?page=19', 20 => '/?page=20']], $window->get());

        /*
         * Test Being Near The End Of The List
         */
        $array = [];
        for ($i = 1; $i <= 20; $i++) {
            $array[$i] = 'item'.$i;
        }
        $p = new LengthAwarePaginator($array, count($array), 1, 17);
        $window = new UrlWindow($p);
        $last = [];
        for ($i = 11; $i <= 20; $i++) {
            $last[$i] = '/?page='.$i;
        }
        $this->assertEquals(['first' => [1 => '/?page=1', 2 => '/?page=2'], 'slider' => null, 'last' => $last], $window->get());
    }

    public function testCustomUrlRangeForAWindowOfLinks()
    {
        $array = [];
        for ($i = 1; $i <= 20; $i++) {
            $array[$i] = 'item'.$i;
        }

        $p = new LengthAwarePaginator($array, count($array), 1, 8);
        $p->onEachSide(1);
        $window = new UrlWindow($p);

        $slider = [];
        for ($i = 7; $i <= 9; $i++) {
            $slider[$i] = '/?page='.$i;
        }

        $this->assertEquals(['first' => [1 => '/?page=1', 2 => '/?page=2'], 'slider' => $slider, 'last' => [19 => '/?page=19', 20 => '/?page=20']], $window->get());
    }
}
