<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\View\Compilers\ComponentTagCompiler;
use Illuminate\View\Component;
use InvalidArgumentException;

class BladeComponentTagCompilerTest extends AbstractBladeTestCase
{
    public function testSlotsCanBeCompiled()
    {
        $result = (new ComponentTagCompiler)->compileSlots('<slot name="foo">
</slot>');

        $this->assertEquals("@slot('foo') \n @endslot", trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiled()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<x-alert/>');

        $this->assertEquals("@component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
\$component->withAttributes([]);
@endcomponent", trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithDataAndAttributes()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<x-alert title="foo" class="bar" wire:model="foo" />');

        $this->assertEquals("@component('Illuminate\Tests\View\Blade\TestAlertComponent', ['title' => 'foo'])
\$component->withAttributes(['class' => 'bar','wire:model' => 'foo']);
@endcomponent", trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithBoundData()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<x-alert :title="$title" class="bar" />');

        $this->assertEquals("@component('Illuminate\Tests\View\Blade\TestAlertComponent', ['title' => \$title])
\$component->withAttributes(['class' => 'bar']);
@endcomponent", trim($result));
    }

    public function testPairedComponentTags()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<x-alert>
</x-alert>');

        $this->assertEquals("@component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
\$component->withAttributes([]);
@endcomponent", trim($result));
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
