<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\View\Component;
use Orchestra\Testbench\TestCase;

class InteractsWithViewsTest extends TestCase
{
    use InteractsWithViews;

    public function testBladeCorrectlyRendersString()
    {
        $string = (string) $this->blade('@if(true)test @endif');

        $this->assertEquals('test ', $string);
    }

    public function testComponentCanAccessPublicProperties()
    {
        $exampleComponent = new class extends Component
        {
            public $foo = 'bar';
            public function render()
            {
                return '';
            }
        };
        $component = $this->component(get_class($exampleComponent));

        $this->assertEquals('bar', $component->foo);
    }
}
