<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;
use Orchestra\Testbench\TestCase;

class ComponentTest extends TestCase
{
    public function testComponentsAreRendered()
    {
        Route::get('/component', function () {
            return new TestComponent('Taylor');
        });

        $response = $this->get('/component');

        $this->assertEquals(200, $response->status());
        $this->assertSame('Hello Taylor', $response->getContent());
    }
}

class TestComponent extends Component
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function render()
    {
        return 'Hello {{ $name }}';
    }
}
