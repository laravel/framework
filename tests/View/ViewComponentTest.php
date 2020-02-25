<?php

namespace Illuminate\Tests\View;

use Illuminate\View\Component;
use PHPUnit\Framework\TestCase;

class ViewComponentTest extends TestCase
{
    public function testDataExposure()
    {
        $component = new TestViewComponent;

        $variables = $component->data();

        $this->assertEquals(10, $variables['votes']);
        $this->assertEquals('world', $variables['hello']());
        $this->assertEquals('taylor', $variables['hello']('taylor'));
    }
}

class TestViewComponent extends Component
{
    public $votes = 10;

    public function render()
    {
        return 'test';
    }

    public function hello($string = 'world')
    {
        return $string;
    }
}
