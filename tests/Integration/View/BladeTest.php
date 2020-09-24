<?php

namespace Illuminate\Tests\Integration\View;

use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class BladeTest extends TestCase
{
    public function test_basic_blade_rendering()
    {
        $view = View::make('hello', ['name' => 'Taylor'])->render();

        $this->assertEquals('Hello Taylor', trim($view));
    }

    public function test_rendering_a_component()
    {
        $view = View::make('uses-panel', ['name' => 'Taylor'])->render();

        $this->assertEquals('<div class="ml-2">
    Hello Taylor
</div>', trim($view));
    }

    public function test_rendering_a_dynamic_component()
    {
        $view = View::make('uses-panel-dynamically', ['name' => 'Taylor'])->render();

        $this->assertEquals('<div class="ml-2" wire:model="foo" wire:model.lazy="bar">
    Hello Taylor
</div>', trim($view));
    }

    public function test_rendering_the_same_dynamic_component_with_different_attributes()
    {
        $view = View::make('varied-dynamic-calls')->render();

        $this->assertEquals('<span class="text-medium">
    Hello Taylor
</span>
  
 <span >
    Hello Samuel
</span>', trim($view));
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('view.paths', [__DIR__.'/templates']);
    }
}
