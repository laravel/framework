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

        $this->assertSame('Hello Taylor', trim($view));
    }

    public function test_rendering_a_component()
    {
        $view = View::make('uses-panel', ['name' => 'Taylor'])->render();

        $this->assertSame('<div class="ml-2">
    Hello Taylor
</div>', trim($view));
    }

    public function test_rendering_a_dynamic_component()
    {
        $view = View::make('uses-panel-dynamically', ['name' => 'Taylor'])->render();

        $this->assertSame('<div class="ml-2" wire:model="foo" wire:model.lazy="bar">
    Hello Taylor
</div>', trim($view));
    }

    public function test_rendering_the_same_dynamic_component_with_different_attributes()
    {
        $view = View::make('varied-dynamic-calls')->render();

        $this->assertSame('<span class="text-medium">
    Hello Taylor
</span>
<span >
    Hello Samuel
</span>', trim($view));
    }

    public function test_inline_link_type_attributes_dont_add_extra_spacing_at_end()
    {
        $view = View::make('uses-link')->render();

        $this->assertSame('This is a sentence with a <a href="https://laravel.com">link</a>.', trim($view));
    }

    public function test_appendable_attributes()
    {
        $view = View::make('uses-appendable-panel', ['name' => 'Taylor', 'withInjectedValue' => true])->render();

        $this->assertSame('<div class="mt-4 bg-gray-100" data-controller="inside-controller outside-controller" foo="bar">
    Hello Taylor
</div>', trim($view));

        $view = View::make('uses-appendable-panel', ['name' => 'Taylor', 'withInjectedValue' => false])->render();

        $this->assertSame('<div class="mt-4 bg-gray-100" data-controller="inside-controller" foo="bar">
    Hello Taylor
</div>', trim($view));
    }

    public function tested_nested_anonymous_attribute_proxying_works_correctly()
    {
        $view = View::make('uses-child-input')->render();

        $this->assertSame('<input class="disabled-class" foo="bar" type="text" disabled />', trim($view));
    }

    public function test_defined_parent_props_are_available_to_child()
    {
        $view = View::make('uses-context')->render();

        $this->assertSame('<div>
    <span>I like the color purple!</span>
    <span>I like the color purple!</div>
</div>', trim($view));
    }

    public function test_child_props_override_parent_context()
    {
        $view = View::make('uses-context-with-override')->render();

        $this->assertSame('<div>
    <span>I like the color purple!</span>
    <span>I like the color pink!</div>
</div>', trim($view));
    }

    public function test_default_values_are_used_in_parent_and_child_when_not_set()
    {
        $view = View::make('uses-context-default')->render();

        $this->assertSame('<div>
    <span>I like the color red!</span>
    <span>I like the color blue!</div>
</div>', trim($view));
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('view.paths', [__DIR__.'/templates']);
    }
}
