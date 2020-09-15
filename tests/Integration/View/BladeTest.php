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

        $this->assertEquals('<div class="ml-2" wire:model="foo">
    Hello Taylor
</div>', trim($view));
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('view.paths', [__DIR__.'/templates']);
    }
}
