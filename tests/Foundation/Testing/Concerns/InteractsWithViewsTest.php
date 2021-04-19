<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;

class InteractsWithViewsTest extends TestCase
{
    use InteractsWithViews;

    public function testBladeCorrectlyRendersString()
    {
        $string = (string) $this->blade("@if(true)test @endif");

        $this->assertEquals('test ', $string);
    }
}
