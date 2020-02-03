<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\View\Compilers\ComponentTagCompiler;
use Illuminate\View\Component;
use Mockery;

class BladeComponentTagCompilerTest extends AbstractBladeTestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testSlotsCanBeCompiled()
    {
        $result = (new ComponentTagCompiler)->compileSlots('<slot name="foo">
</slot>');

        $this->assertEquals("@slot('foo') \n @endslot", trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiled()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<div><x-alert/></div>');

        $this->assertEquals("<div> @component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
<?php \$component->withAttributes([]); ?>
@endcomponentClass</div>", trim($result));
    }

    public function testClassNamesCanBeGuessed()
    {
        $container = new Container;
        $container->instance(Application::class, $app = Mockery::mock(Application::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        Container::setInstance($container);

        $result = (new ComponentTagCompiler([]))->guessClassName('alert');

        $this->assertEquals("App\View\Components\Alert", trim($result));

        Container::setInstance(null);
    }

    public function testClassNamesCanBeGuessedWithNamespaces()
    {
        $container = new Container;
        $container->instance(Application::class, $app = Mockery::mock(Application::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        Container::setInstance($container);

        $result = (new ComponentTagCompiler([]))->guessClassName('base:alert');

        $this->assertEquals("App\View\Components\Base\Alert", trim($result));

        Container::setInstance(null);
    }

    public function testComponentsCanBeCompiledWithHyphenAttributes()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<x-alert class="bar" wire:model="foo" x-on:click="bar" @click="baz" />');

        $this->assertEquals("@component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
<?php \$component->withAttributes(['class' => 'bar','wire:model' => 'foo','x-on:click' => 'bar','@click' => 'baz']); ?>
@endcomponentClass", trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithDataAndAttributes()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<x-alert title="foo" class="bar" wire:model="foo" />');

        $this->assertEquals("@component('Illuminate\Tests\View\Blade\TestAlertComponent', ['title' => 'foo'])
<?php \$component->withAttributes(['class' => 'bar','wire:model' => 'foo']); ?>
@endcomponentClass", trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithBoundData()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<x-alert :title="$title" class="bar" />');

        $this->assertEquals("@component('Illuminate\Tests\View\Blade\TestAlertComponent', ['title' => \$title])
<?php \$component->withAttributes(['class' => 'bar']); ?>
@endcomponentClass", trim($result));
    }

    public function testPairedComponentTags()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<x-alert>
</x-alert>');

        $this->assertEquals("@component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
<?php \$component->withAttributes([]); ?>
@endcomponentClass", trim($result));
    }
}

class TestAlertComponent extends Component
{
    public $title;

    public function __construct($title = 'foo')
    {
        $this->title = $title;
    }

    public function view()
    {
        return 'alert';
    }
}
