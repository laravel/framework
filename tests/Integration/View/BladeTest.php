<?php

namespace Illuminate\Tests\Integration\View;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\View\Component;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class BladeTest extends TestCase
{
    public function test_rendering_blade_string()
    {
        $this->assertSame('Hello Taylor', Blade::render('Hello {{ $name }}', ['name' => 'Taylor']));
    }

    public function test_rendering_blade_long_maxpathlen_string()
    {
        $longString = str_repeat('a', PHP_MAXPATHLEN);

        $result = Blade::render($longString.'{{ $name }}', ['name' => 'a']);

        $this->assertSame($longString.'a', $result);
    }

    #[RunInSeparateProcess]
    public function test_rendering_blade_long_maxpathlen_string_with_exact_length()
    {
        // The PHP_MAXPATHLEN restriction is only active, if
        // open_basedir is set and active. Otherwise, the check
        // for the PHP_MAXPATHLEN is not active.
        if (ini_get('open_basedir') === '') {
            $openBaseDir = windows_os() ? explode('\\', __DIR__)[0].'\\'.';'.sys_get_temp_dir() : '/';
            $iniSet = ini_set(
                'open_basedir',
                $openBaseDir
            );

            $this->assertNotFalse($iniSet, 'Could not set config for open_basedir.');
        }

        for ($i = PHP_MAXPATHLEN - 200; $i <= PHP_MAXPATHLEN + 1; $i++) {
            $longString = str_repeat('x', $i);

            $result = Blade::render($longString);

            $this->assertSame($longString, $result);
        }
    }

    public function test_rendering_blade_component_instance()
    {
        $component = new HelloComponent('Taylor');

        $this->assertSame('Hello Taylor', Blade::renderComponent($component));
    }

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

    public function test_consume_defaults()
    {
        $view = View::make('consume')->render();

        $this->assertSame('<h1>Menu</h1>
<div>Slot: A, Color: orange, Default: foo</div>
<div>Slot: B, Color: red, Default: foo</div>
<div>Slot: C, Color: blue, Default: foo</div>
<div>Slot: D, Color: red, Default: foo</div>
<div>Slot: E, Color: red, Default: foo</div>
<div>Slot: F, Color: yellow, Default: foo</div>', trim($view));
    }

    public function test_consume_with_props()
    {
        $view = View::make('consume', ['color' => 'rebeccapurple'])->render();

        $this->assertSame('<h1>Menu</h1>
<div>Slot: A, Color: orange, Default: foo</div>
<div>Slot: B, Color: rebeccapurple, Default: foo</div>
<div>Slot: C, Color: blue, Default: foo</div>
<div>Slot: D, Color: rebeccapurple, Default: foo</div>
<div>Slot: E, Color: rebeccapurple, Default: foo</div>
<div>Slot: F, Color: yellow, Default: foo</div>', trim($view));
    }

    public function test_name_attribute_can_be_used_if_using_short_slot_names()
    {
        $content = Blade::render('<x-input-with-slot>
    <x-slot:input name="my_form_field" class="text-input-lg" data-test="data">Test</x-slot:input>
</x-input-with-slot>');

        $this->assertSame('<div>
    <input type="text" class="input text-input-lg" data-test="data" name="my_form_field" />
</div>', trim($content));
    }

    public function test_name_attribute_cant_be_used_if_not_using_short_slot_names()
    {
        $content = Blade::render('<x-input-with-slot>
    <x-slot name="input" class="text-input-lg" data-test="data">Test</x-slot>
</x-input-with-slot>');

        $this->assertSame('<div>
    <input type="text" class="input text-input-lg" data-test="data" />
</div>', trim($content));
    }

    public function test_bound_name_attribute_can_be_used_if_using_short_slot_names()
    {
        $content = Blade::render('<x-input-with-slot>
    <x-slot:input :name="\'my_form_field\'" class="text-input-lg" data-test="data">Test</x-slot:input>
</x-input-with-slot>');

        $this->assertSame('<div>
    <input type="text" class="input text-input-lg" data-test="data" name="my_form_field" />
</div>', trim($content));
    }

    public function test_bound_name_attribute_can_be_used_if_using_short_slot_names_and_not_first_attribute()
    {
        $content = Blade::render('<x-input-with-slot>
    <x-slot:input class="text-input-lg" :name="\'my_form_field\'" data-test="data">Test</x-slot:input>
</x-input-with-slot>');

        $this->assertSame('<div>
    <input type="text" class="input text-input-lg" name="my_form_field" data-test="data" />
</div>', trim($content));
    }

    public function testViewCacheCommandHandlesConfiguredBladeExtensions()
    {
        $this->artisan('view:clear');

        View::addExtension('sh', 'blade');
        $this->artisan('view:cache');

        $compiledFiles = Finder::create()->in(Config::get('view.compiled'))->files();
        $found = collect($compiledFiles)
            ->contains(fn (SplFileInfo $file) => str_contains($file->getContents(), 'echo "<?php echo e($scriptMessage); ?>" > output.log'));
        $this->assertTrue($found);

        $this->artisan('view:clear');
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('view.paths', [__DIR__.'/templates']);
    }
}

class HelloComponent extends Component
{
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function render()
    {
        return 'Hello {{ $name }}';
    }
}
