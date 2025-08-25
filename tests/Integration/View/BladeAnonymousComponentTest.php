<?php

namespace Illuminate\Tests\Integration\View;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class BladeAnonymousComponentTest extends TestCase
{
    public function test_anonymous_components_with_custom_paths_can_be_rendered()
    {
        Blade::anonymousComponentPath(__DIR__.'/anonymous-components-1', 'layouts');
        Blade::anonymousComponentPath(__DIR__.'/anonymous-components-2');

        $view = View::make('page')->render();

        $this->assertTrue(str_contains($view, 'Panel content.'));
        $this->assertTrue(str_contains($view, 'class="app-layout"'));
        $this->assertTrue(str_contains($view, 'class="danger-button"'));
    }

    public function test_anonymous_components_with_custom_paths_cant_be_rendered_as_normal_views()
    {
        $this->expectException(InvalidArgumentException::class);

        Blade::anonymousComponentPath(__DIR__.'/anonymous-components-1', 'layouts');
        Blade::anonymousComponentPath(__DIR__.'/anonymous-components-2');

        $view = View::make('layouts::app')->render();
    }

    public function test_anonymous_components_with_custom_paths_cant_be_rendered_as_normal_views_even_with_no_prefix()
    {
        $this->expectException(InvalidArgumentException::class);

        Blade::anonymousComponentPath(__DIR__.'/anonymous-components-1', 'layouts');
        Blade::anonymousComponentPath(__DIR__.'/anonymous-components-2');

        $view = View::make('panel')->render();
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('view.paths', [__DIR__.'/anonymous-components-templates']);
    }
}
