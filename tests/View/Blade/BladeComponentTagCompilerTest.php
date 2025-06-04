<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\ComponentTagCompiler;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;
use Mockery as m;

class BladeComponentTagCompilerTest extends AbstractBladeTestCase
{
    public function testSlotsCanBeCompiled()
    {
        $this->mockViewFactory();
        $result = $this->compiler()->compileSlots('<x-slot name="foo">
</x-slot>');

        $this->assertSame(
            "@slot('foo', null, []) \n".' @endslot',
            str_replace("\r\n", "\n", trim($result))
        );
    }

    public function testInlineSlotsCanBeCompiled()
    {
        $this->mockViewFactory();
        $result = $this->compiler()->compileSlots('<x-slot:foo>
</x-slot>');

        $this->assertSame(
            "@slot('foo', null, []) \n".' @endslot',
            str_replace("\r\n", "\n", trim($result))
        );
    }

    public function testDynamicSlotsCanBeCompiled()
    {
        $this->mockViewFactory();
        $result = $this->compiler()->compileSlots('<x-slot :name="$foo">
</x-slot>');

        $this->assertSame(
            "@slot(\$foo, null, []) \n".' @endslot',
            str_replace("\r\n", "\n", trim($result))
        );
    }

    public function testDynamicSlotsCanBeCompiledWithKeyOfObjects()
    {
        $this->mockViewFactory();
        $result = $this->compiler()->compileSlots('<x-slot :name="$foo->name">
</x-slot>');

        $this->assertSame(
            "@slot(\$foo->name, null, []) \n".' @endslot',
            str_replace("\r\n", "\n", trim($result))
        );
    }

    public function testSlotsWithAttributesCanBeCompiled()
    {
        $this->mockViewFactory();
        $result = $this->compiler()->compileSlots('<x-slot name="foo" class="font-bold">
</x-slot>');

        $this->assertSame(
            "@slot('foo', null, ['class' => 'font-bold']) \n".' @endslot',
            str_replace("\r\n", "\n", trim($result))
        );
    }

    public function testInlineSlotsWithAttributesCanBeCompiled()
    {
        $this->mockViewFactory();
        $result = $this->compiler()->compileSlots('<x-slot:foo class="font-bold">
</x-slot>');

        $this->assertSame(
            "@slot('foo', null, ['class' => 'font-bold']) \n".' @endslot',
            str_replace("\r\n", "\n", trim($result))
        );
    }

    public function testSlotsWithDynamicAttributesCanBeCompiled()
    {
        $this->mockViewFactory();
        $result = $this->compiler()->compileSlots('<x-slot name="foo" :class="$classes">
</x-slot>');

        $this->assertSame(
            "@slot('foo', null, ['class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\$classes)]) \n".' @endslot',
            str_replace("\r\n", "\n", trim($result))
        );
    }

    public function testSlotsWithClassDirectiveCanBeCompiled()
    {
        $this->mockViewFactory();
        $result = $this->compiler()->compileSlots('<x-slot name="foo" @class($classes)>
</x-slot>');

        $this->assertSame(
            "@slot('foo', null, ['class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssClasses(\$classes))]) \n".' @endslot',
            str_replace("\r\n", "\n", trim($result))
        );
    }

    public function testSlotsWithStyleDirectiveCanBeCompiled()
    {
        $this->mockViewFactory();
        $result = $this->compiler()->compileSlots('<x-slot name="foo" @style($styles)>
</x-slot>');

        $this->assertSame(
            "@slot('foo', null, ['style' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssStyles(\$styles))]) \n".' @endslot',
            str_replace("\r\n", "\n", trim($result))
        );
    }

    public function testBasicComponentParsing()
    {
        $this->mockViewFactory();

        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert type="foo" limit="5" @click="foo" wire:click="changePlan(\'{{ $plan }}\')" required x-intersect.margin.-50%.0px="visibleSection = \'profile\'" /><x-alert /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestAlertComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['type' => 'foo','limit' => '5','@click' => 'foo','wire:click' => 'changePlan(\''.e(\$plan).'\')','required' => true,'x-intersect.margin.-50%.0px' => 'visibleSection = \'profile\'']); ?>\n".
"@endComponentClass##END-COMPONENT-CLASS####BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestAlertComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testNestedDefaultComponentParsing()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $app->shouldReceive('getNamespace')->once()->andReturn('App\\');
        Container::setInstance($container);

        $result = $this->compiler()->compileTags('<div><x-card /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('App\View\Components\Card\Card', 'card', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\App\View\Components\Card\Card::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testCustomNamespaceNestedDefaultComponentParsing()
    {
        $this->mockViewFactory();
        $result = $this->compiler(namespaces: ['nightshade' => 'Nightshade\\View\\Components'])->compileTags('<div><x-nightshade::accordion /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Nightshade\View\Components\Accordion\Accordion', 'nightshade::accordion', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Nightshade\View\Components\Accordion\Accordion::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testBasicComponentWithEmptyAttributesParsing()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert type="" limit=\'\' @click="" required /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestAlertComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['type' => '','limit' => '','@click' => '','required' => true]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testDataCamelCasing()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile user-id="1"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => '1'])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestProfileComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonData()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :user-id="1"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => 1])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestProfileComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonDataShortSyntax()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :$userId></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => \$userId])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestProfileComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonDataWithStaticClassProperty()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :userId="User::$id"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => User::\$id])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestProfileComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonDataWithStaticClassPropertyAndMultipleAttributes()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['input' => TestInputComponent::class])->compileTags('<x-input :label="Input::$label" :$name value="Joe"></x-input>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestInputComponent', 'input', ['label' => Input::\$label,'name' => \$name,'value' => 'Joe'])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestInputComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));

        $result = $this->compiler(['input' => TestInputComponent::class])->compileTags('<x-input value="Joe" :$name :label="Input::$label"></x-input>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestInputComponent', 'input', ['value' => 'Joe','name' => \$name,'label' => Input::\$label])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestInputComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testSelfClosingComponentWithColonDataShortSyntax()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :$userId/>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => \$userId])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestProfileComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testSelfClosingComponentWithColonDataAndStaticClassPropertyShortSyntax()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :userId="User::$id"/>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => User::\$id])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestProfileComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testSelfClosingComponentWithColonDataMultipleAttributesAndStaticClassPropertyShortSyntax()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['input' => TestInputComponent::class])->compileTags('<x-input :label="Input::$label" value="Joe" :$name />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestInputComponent', 'input', ['label' => Input::\$label,'value' => 'Joe','name' => \$name])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestInputComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));

        $result = $this->compiler(['input' => TestInputComponent::class])->compileTags('<x-input :$name :label="Input::$label" value="Joe" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestInputComponent', 'input', ['name' => \$name,'label' => Input::\$label,'value' => 'Joe'])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestInputComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testEscapedColonAttribute()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :user-id="1" ::title="user.name"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => 1])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestProfileComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([':title' => 'user.name']); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonAttributesIsEscapedIfStrings()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :src="\'foo\'"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestProfileComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('foo')]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testClassDirective()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile @class(["bar"=>true])></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestProfileComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssClasses(['bar'=>true]))]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testStyleDirective()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile @style(["bar"=>true])></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestProfileComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['style' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssStyles(['bar'=>true]))]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonNestedComponentParsing()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['foo:alert' => TestAlertComponent::class])->compileTags('<x-foo:alert></x-foo:alert>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'foo:alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestAlertComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonStartingNestedComponentParsing()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['foo:alert' => TestAlertComponent::class])->compileTags('<x:foo:alert></x-foo:alert>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'foo:alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestAlertComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiled()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert/></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestAlertComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testClassesCanBeFoundByComponents()
    {
        $this->mockViewFactory();
        $compiler = $this->compiler(namespaces: ['nightshade' => 'Nightshade\\View\\Components']);

        $result = $compiler->findClassByComponent('nightshade::calendar');
        $this->assertSame('Nightshade\\View\\Components\\Calendar', trim($result));

        $result = $compiler->findClassByComponent('nightshade::accordion');
        $this->assertSame('Nightshade\\View\\Components\\Accordion\\Accordion', trim($result));
    }

    public function testClassNamesCanBeGuessed()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $app->shouldReceive('getNamespace')->once()->andReturn('App\\');
        Container::setInstance($container);

        $result = $this->compiler()->guessClassName('alert');

        $this->assertSame("App\View\Components\Alert", trim($result));

        Container::setInstance(null);
    }

    public function testClassNamesCanBeGuessedWithNamespaces()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $app->shouldReceive('getNamespace')->once()->andReturn('App\\');
        Container::setInstance($container);

        $result = $this->compiler()->guessClassName('base.alert');

        $this->assertSame("App\View\Components\Base\Alert", trim($result));

        Container::setInstance(null);
    }

    public function testComponentsCanBeCompiledWithHyphenAttributes()
    {
        $this->mockViewFactory();

        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert class="bar" wire:model="foo" x-on:click="bar" @click="baz" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestAlertComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','wire:model' => 'foo','x-on:click' => 'bar','@click' => 'baz']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithDataAndAttributes()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert title="foo" class="bar" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', ['title' => 'foo'])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestAlertComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','wire:model' => 'foo']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testComponentCanReceiveAttributeBag()
    {
        $this->mockViewFactory();

        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile class="bar" {{ $attributes }} wire:model="foo"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestProfileComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\$attributes),'wire:model' => 'foo']); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testSelfClosingComponentCanReceiveAttributeBag()
    {
        $this->mockViewFactory();

        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert title="foo" class="bar" {{ $attributes->merge([\'class\' => \'test\']) }} wire:model="foo" /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', ['title' => 'foo'])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestAlertComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\$attributes->merge(['class' => 'test'])),'wire:model' => 'foo']); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testComponentsCanHaveAttachedWord()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile></x-profile>Words');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestProfileComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##Words", trim($result));
    }

    public function testSelfClosingComponentsCanHaveAttachedWord()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert/>Words');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestAlertComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##Words', trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithBoundData()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert :title="$title" class="bar" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', ['title' => \$title])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestAlertComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testPairedComponentTags()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert>
</x-alert>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\Tests\View\Blade\TestAlertComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>
 @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testClasslessComponents()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $app->shouldReceive('getNamespace')->once()->andReturn('App\\');
        $factory->shouldReceive('exists')->once()->andReturn(true);
        Container::setInstance($container);

        $result = $this->compiler()->compileTags('<x-anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'anonymous-component', ['view' => 'components.anonymous-component','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testClasslessComponentsWithIndexView()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(false, true);
        Container::setInstance($container);

        $result = $this->compiler()->compileTags('<x-anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'anonymous-component', ['view' => 'components.anonymous-component.index','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testClasslessComponentsWithComponentView()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(false, false, true);
        Container::setInstance($container);

        $result = $this->compiler()->compileTags('<x-anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'anonymous-component', ['view' => 'components.anonymous-component.anonymous-component','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testPackagesClasslessComponents()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(true);
        Container::setInstance($container);

        $result = $this->compiler()->compileTags('<x-package::anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'package::anonymous-component', ['view' => 'package::components.anonymous-component','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testClasslessComponentsWithAnonymousComponentNamespace()
    {
        $container = new Container;

        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));

        $app->shouldReceive('getNamespace')->once()->andReturn('App\\');
        $factory->shouldReceive('exists')->times(4)->andReturnUsing(function ($arg) {
            // In our test, we'll do as if the 'public.frontend.anonymous-component'
            // view exists and not the others.
            return $arg === 'public.frontend.anonymous-component';
        });

        Container::setInstance($container);

        $blade = m::mock(BladeCompiler::class)->makePartial();

        $blade->shouldReceive('getAnonymousComponentNamespaces')->once()->andReturn([
            'frontend' => 'public.frontend',
        ]);

        $compiler = $this->compiler([], [], $blade);

        $result = $compiler->compileTags('<x-frontend::anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'frontend::anonymous-component', ['view' => 'public.frontend.anonymous-component','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testClasslessComponentsWithAnonymousComponentNamespaceWithIndexView()
    {
        $container = new Container;

        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));

        $app->shouldReceive('getNamespace')->once()->andReturn('App\\');
        $factory->shouldReceive('exists')->times(5)->andReturnUsing(function (string $viewNameBeingCheckedForExistence) {
            // In our test, we'll do as if the 'public.frontend.anonymous-component'
            // view exists and not the others.
            return $viewNameBeingCheckedForExistence === 'admin.auth.components.anonymous-component.index';
        });

        Container::setInstance($container);

        $blade = m::mock(BladeCompiler::class)->makePartial();

        $blade->shouldReceive('getAnonymousComponentNamespaces')->once()->andReturn([
            'admin.auth' => 'admin.auth.components',
        ]);

        $compiler = $this->compiler([], [], $blade);

        $result = $compiler->compileTags('<x-admin.auth::anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'admin.auth::anonymous-component', ['view' => 'admin.auth.components.anonymous-component.index','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testClasslessComponentsWithAnonymousComponentNamespaceWithComponentView()
    {
        $container = new Container;

        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));

        $app->shouldReceive('getNamespace')->once()->andReturn('App\\');
        $factory->shouldReceive('exists')->times(6)->andReturnUsing(function (string $viewNameBeingCheckedForExistence) {
            // In our test, we'll do as if the 'public.frontend.anonymous-component'
            // view exists and not the others.
            return $viewNameBeingCheckedForExistence === 'admin.auth.components.anonymous-component.anonymous-component';
        });

        Container::setInstance($container);

        $blade = m::mock(BladeCompiler::class)->makePartial();

        $blade->shouldReceive('getAnonymousComponentNamespaces')->once()->andReturn([
            'admin.auth' => 'admin.auth.components',
        ]);

        $compiler = $this->compiler([], [], $blade);

        $result = $compiler->compileTags('<x-admin.auth::anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'admin.auth::anonymous-component', ['view' => 'admin.auth.components.anonymous-component.anonymous-component','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testClasslessComponentsWithAnonymousComponentPath()
    {
        $container = new Container;

        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));

        $app->shouldReceive('getNamespace')->once()->andReturn('App\\');

        $factory->shouldReceive('exists')->andReturnUsing(function ($arg) {
            return $arg === hash('xxh128', 'test-directory').'::panel.index';
        });

        Container::setInstance($container);

        $blade = m::mock(BladeCompiler::class)->makePartial();

        $blade->shouldReceive('getAnonymousComponentPaths')->once()->andReturn([
            ['path' => 'test-directory', 'prefix' => null, 'prefixHash' => hash('xxh128', 'test-directory')],
        ]);

        $compiler = $this->compiler([], [], $blade);

        $result = $compiler->compileTags('<x-panel />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'panel', ['view' => '".hash('xxh128', 'test-directory')."::panel.index','data' => []])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testClasslessComponentsWithAnonymousComponentPathComponentName()
    {
        $container = new Container;

        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));

        $app->shouldReceive('getNamespace')->once()->andReturn('App\\');

        $factory->shouldReceive('exists')->andReturnUsing(function ($arg) {
            return $arg === md5('test-directory').'::panel.panel';
        });

        Container::setInstance($container);

        $blade = m::mock(BladeCompiler::class)->makePartial();

        $blade->shouldReceive('getAnonymousComponentPaths')->once()->andReturn([
            ['path' => 'test-directory', 'prefix' => null, 'prefixHash' => md5('test-directory')],
        ]);

        $compiler = $this->compiler([], [], $blade);

        $result = $compiler->compileTags('<x-panel />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'panel', ['view' => '".md5('test-directory')."::panel.panel','data' => []])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testClasslessIndexComponentsWithAnonymousComponentPath()
    {
        $container = new Container;

        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));

        $app->shouldReceive('getNamespace')->once()->andReturn('App\\');

        $factory->shouldReceive('exists')->andReturnUsing(function ($arg) {
            return $arg === hash('xxh128', 'test-directory').'::panel';
        });

        Container::setInstance($container);

        $blade = m::mock(BladeCompiler::class)->makePartial();

        $blade->shouldReceive('getAnonymousComponentPaths')->once()->andReturn([
            ['path' => 'test-directory', 'prefix' => null, 'prefixHash' => hash('xxh128', 'test-directory')],
        ]);

        $compiler = $this->compiler([], [], $blade);

        $result = $compiler->compileTags('<x-panel />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'panel', ['view' => '".hash('xxh128', 'test-directory')."::panel','data' => []])
<?php if (isset(\$attributes) && \$attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php \$attributes = \$attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testAttributeSanitization()
    {
        $this->mockViewFactory();
        $class = new class
        {
            public function __toString()
            {
                return '<hi>';
            }
        };

        $model = new class extends Model {
        };

        $paginator = new class extends AbstractPaginator {
        };

        $this->assertEquals(e('<hi>'), BladeCompiler::sanitizeComponentAttribute('<hi>'));
        $this->assertEquals(e('1'), BladeCompiler::sanitizeComponentAttribute('1'));
        $this->assertEquals(1, BladeCompiler::sanitizeComponentAttribute(1));
        $this->assertEquals(e('<hi>'), BladeCompiler::sanitizeComponentAttribute($class));
        $this->assertSame($model, BladeCompiler::sanitizeComponentAttribute($model));
        $this->assertSame($paginator, BladeCompiler::sanitizeComponentAttribute($paginator));
    }

    public function testItThrowsAnExceptionForNonExistingAliases()
    {
        $this->mockViewFactory(false);

        $this->expectException(InvalidArgumentException::class);

        $this->compiler(['alert' => 'foo.bar'])->compileTags('<x-alert />');
    }

    public function testItThrowsAnExceptionForNonExistingClass()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $app->shouldReceive('getNamespace')->once()->andReturn('App\\');
        $factory->shouldReceive('exists')->times(3)->andReturn(false);
        Container::setInstance($container);

        $this->expectException(InvalidArgumentException::class);

        $this->compiler()->compileTags('<x-alert />');
    }

    public function testAttributesTreatedAsPropsAreRemovedFromFinalAttributes()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $container->alias(Factory::class, 'view');
        $app->shouldReceive('getNamespace')->never()->andReturn('App\\');
        $factory->shouldReceive('exists')->never();

        Container::setInstance($container);

        $attributes = new ComponentAttributeBag(['userId' => 'bar', 'other' => 'ok']);

        $component = m::mock(Component::class);
        $component->shouldReceive('withName')->with('profile')->once();
        $component->shouldReceive('shouldRender')->once()->andReturn(true);
        $component->shouldReceive('resolveView')->once()->andReturn('');
        $component->shouldReceive('data')->once()->andReturn([]);
        $component->shouldReceive('withAttributes')->with(['attributes' => new ComponentAttributeBag(['other' => 'ok'])])->once();

        Component::resolveComponentsUsing(fn () => $component);

        $__env = m::mock(\Illuminate\View\Factory::class);
        $__env->shouldReceive('startComponent')->once();
        $__env->shouldReceive('renderComponent')->once();

        $template = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile {{ $attributes }} />');
        $template = $this->compiler->compileString($template);

        ob_start();
        eval(" ?> $template <?php ");
        ob_get_clean();

        $this->assertSame($attributes->get('userId'), 'bar');
        $this->assertSame($attributes->get('other'), 'ok');
    }

    public function testOriginalAttributesAreRestoredAfterRenderingChildComponentWithProps()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $container->alias(Factory::class, 'view');
        $app->shouldReceive('getNamespace')->never()->andReturn('App\\');
        $factory->shouldReceive('exists')->never();

        Container::setInstance($container);

        $attributes = new ComponentAttributeBag(['userId' => 'bar', 'other' => 'ok']);

        $containerComponent = m::mock(Component::class);
        $containerComponent->shouldReceive('withName')->with('container')->once();
        $containerComponent->shouldReceive('shouldRender')->once()->andReturn(true);
        $containerComponent->shouldReceive('resolveView')->once()->andReturn('');
        $containerComponent->shouldReceive('data')->once()->andReturn([]);
        $containerComponent->shouldReceive('withAttributes')->once();

        $profileComponent = m::mock(Component::class);
        $profileComponent->shouldReceive('withName')->with('profile')->once();
        $profileComponent->shouldReceive('shouldRender')->once()->andReturn(true);
        $profileComponent->shouldReceive('resolveView')->once()->andReturn('');
        $profileComponent->shouldReceive('data')->once()->andReturn([]);
        $profileComponent->shouldReceive('withAttributes')->with(['attributes' => new ComponentAttributeBag(['other' => 'ok'])])->once();

        Component::resolveComponentsUsing(fn ($component) => match ($component) {
            TestContainerComponent::class => $containerComponent,
            TestProfileComponent::class => $profileComponent,
        });

        $__env = m::mock(\Illuminate\View\Factory::class);
        $__env->shouldReceive('startComponent')->twice();
        $__env->shouldReceive('renderComponent')->twice();

        $template = $this->compiler([
            'container' => TestContainerComponent::class,
            'profile' => TestProfileComponent::class,
        ])->compileTags('<x-container><x-profile {{ $attributes }} /></x-container>');
        $template = $this->compiler->compileString($template);

        ob_start();
        eval(" ?> $template <?php ");
        ob_get_clean();

        $this->assertSame($attributes->get('userId'), 'bar');
        $this->assertSame($attributes->get('other'), 'ok');
    }

    protected function mockViewFactory($existsSucceeds = true)
    {
        $container = new Container;
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $container->alias(Factory::class, 'view');
        $factory->shouldReceive('exists')->andReturn($existsSucceeds);
        Container::setInstance($container);
    }

    protected function compiler(array $aliases = [], array $namespaces = [], ?BladeCompiler $blade = null)
    {
        return new ComponentTagCompiler(
            $aliases, $namespaces, $blade
        );
    }
}

class TestAlertComponent extends Component
{
    public $title;

    public function __construct($title = 'foo', $userId = 1)
    {
        $this->title = $title;
    }

    public function render()
    {
        return 'alert';
    }
}

class TestProfileComponent extends Component
{
    public $userId;

    public function __construct($userId = 'foo')
    {
        $this->userId = $userId;
    }

    public function render()
    {
        return 'profile';
    }
}

class TestInputComponent extends Component
{
    public $userId;

    public function __construct($name, $label, $value)
    {
        $this->name = $name;
        $this->label = $label;
        $this->value = $value;
    }

    public function render()
    {
        return 'input';
    }
}

class TestContainerComponent extends Component
{
    public function render()
    {
        return 'container';
    }
}

namespace App\View\Components\Card;

use Illuminate\View\Component;

class Card extends Component
{
    public function render()
    {
        return 'card';
    }
}

namespace Nightshade\View\Components;

use Illuminate\View\Component;

class Calendar extends Component
{
    public function render()
    {
        return 'calendar';
    }
}

namespace Nightshade\View\Components\Accordion;

use Illuminate\View\Component;

class Accordion extends Component
{
    public function render()
    {
        return 'accordion';
    }
}
