<?php

namespace Illuminate\Tests\View;

use Illuminate\View\Component;
use PHPUnit\Framework\TestCase;

class ViewComponentTest extends TestCase
{
    public function testAttributeRetrieval()
    {
        $component = new TestViewComponent;
        $component->withAttributes(['class' => 'font-bold', 'name' => 'test']);

        $this->assertEquals('class="mt-4 font-bold" name="test"', (string) $component->attributes(['class' => 'mt-4']));
    }

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

    public function view()
    {
        return 'test';
    }

    public function hello($string = 'world')
    {
        return $string;
    }
}
