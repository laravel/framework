<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\ComponentTagCompiler;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;
use Mockery;

class BladeComponentTagCompilerTest extends AbstractBladeTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSlotsCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot name="foo">
</x-slot>');

        $this->assertSame("@slot('foo') \n".' @endslot', trim($result));
    }

    public function testDynamicSlotsCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot :name="$foo">
</x-slot>');

        $this->assertSame("@slot(\$foo) \n".' @endslot', trim($result));
    }

    public function testBasicComponentParsing()
    {
        $this->mockViewFactory();

        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert type="foo" limit="5" @click="foo" wire:click="changePlan(\'{{ $plan }}\')" required /><x-alert /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['type' => 'foo','limit' => '5','@click' => 'foo','wire:click' => 'changePlan(\''.e(\$plan).'\')','required' => true]); ?>\n".
"@endComponentClass##END-COMPONENT-CLASS####BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testBasicComponentWithEmptyAttributesParsing()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert type="" limit=\'\' @click="" required /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['type' => '','limit' => '','@click' => '','required' => true]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testDataCamelCasing()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile user-id="1"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => '1'])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonData()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :user-id="1"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => 1])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testEscapedColonAttribute()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :user-id="1" ::title="user.name"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => 1])
<?php \$component->withAttributes([':title' => 'user.name']); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonAttributesIsEscapedIfStrings()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :src="\'foo\'"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('foo')]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonNestedComponentParsing()
    {
        $result = $this->compiler(['foo:alert' => TestAlertComponent::class])->compileTags('<x-foo:alert></x-foo:alert>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'foo:alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonStartingNestedComponentParsing()
    {
        $result = $this->compiler(['foo:alert' => TestAlertComponent::class])->compileTags('<x:foo:alert></x-foo:alert>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'foo:alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiled()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert/></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testClassNamesCanBeGuessed()
    {
        $container = new Container;
        $container->instance(Application::class, $app = Mockery::mock(Application::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        Container::setInstance($container);

        $result = $this->compiler()->guessClassName('alert');

        $this->assertSame("App\View\Components\Alert", trim($result));

        Container::setInstance(null);
    }

    public function testClassNamesCanBeGuessedWithNamespaces()
    {
        $container = new Container;
        $container->instance(Application::class, $app = Mockery::mock(Application::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
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
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','wire:model' => 'foo','x-on:click' => 'bar','@click' => 'baz']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithDataAndAttributes()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert title="foo" class="bar" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', ['title' => 'foo'])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','wire:model' => 'foo']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testComponentCanReceiveAttributeBag()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile class="bar" {{ $attributes }} wire:model="foo"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\$attributes),'wire:model' => 'foo']); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testSelfClosingComponentCanReceiveAttributeBag()
    {
        $this->mockViewFactory();

        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert title="foo" class="bar" {{ $attributes->merge([\'class\' => \'test\']) }} wire:model="foo" /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', ['title' => 'foo'])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\$attributes->merge(['class' => 'test'])),'wire:model' => 'foo']); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testComponentsCanHaveAttachedWord()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile></x-profile>Words');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##Words", trim($result));
    }

    public function testSelfClosingComponentsCanHaveAttachedWord()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert/>Words');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##Words', trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithBoundData()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert :title="$title" class="bar" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', ['title' => \$title])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testPairedComponentTags()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert>
</x-alert>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>
 @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testClasslessComponents()
    {
        $container = new Container;
        $container->instance(Application::class, $app = Mockery::mock(Application::class));
        $container->instance(Factory::class, $factory = Mockery::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(true);
        Container::setInstance($container);

        $result = $this->compiler()->compileTags('<x-anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'anonymous-component', ['view' => 'components.anonymous-component','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testPackagesClasslessComponents()
    {
        $container = new Container;
        $container->instance(Application::class, $app = Mockery::mock(Application::class));
        $container->instance(Factory::class, $factory = Mockery::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(true);
        Container::setInstance($container);

        $result = $this->compiler()->compileTags('<x-package::anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'package::anonymous-component', ['view' => 'package::components.anonymous-component','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testAttributeSanitization()
    {
        $class = new class {
            public function __toString()
            {
                return '<hi>';
            }
        };

        $this->assertEquals(e('<hi>'), BladeCompiler::sanitizeComponentAttribute('<hi>'));
        $this->assertEquals(e('1'), BladeCompiler::sanitizeComponentAttribute('1'));
        $this->assertEquals(1, BladeCompiler::sanitizeComponentAttribute(1));
        $this->assertEquals(e('<hi>'), BladeCompiler::sanitizeComponentAttribute($class));
    }

    public function testItThrowsAnExceptionForNonExistingAliases()
    {
        $container = new Container;
        $container->instance(Application::class, $app = Mockery::mock(Application::class));
        $container->instance(Factory::class, $factory = Mockery::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(false);
        Container::setInstance($container);

        $this->expectException(InvalidArgumentException::class);

        $this->compiler(['alert' => 'foo.bar'])->compileTags('<x-alert />');
    }

    public function testItThrowsAnExceptionForNonExistingClass()
    {
        $container = new Container;
        $container->instance(Application::class, $app = Mockery::mock(Application::class));
        $container->instance(Factory::class, $factory = Mockery::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(false);
        Container::setInstance($container);

        $this->expectException(InvalidArgumentException::class);

        $this->compiler()->compileTags('<x-alert />');
    }

    public function testAttributesTreatedAsPropsAreRemovedFromFinalAttributes()
    {
        $container = new Container;
        $container->instance(Application::class, $app = Mockery::mock(Application::class));
        $container->instance(Factory::class, $factory = Mockery::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(false);
        Container::setInstance($container);

        $attributes = new ComponentAttributeBag(['userId' => 'bar', 'other' => 'ok']);

        $component = Mockery::mock(\Illuminate\View\Component::class);
        $component->shouldReceive('withName', 'test');
        $component->shouldReceive('shouldRender')->andReturn(true);
        $component->shouldReceive('resolveView')->andReturn('');
        $component->shouldReceive('data')->andReturn([]);
        $component->shouldReceive('withAttributes');

        $__env = Mockery::mock(\Illuminate\View\Factory::class);
        $__env->shouldReceive('getContainer->make')->with(TestProfileComponent::class, ['userId' => 'bar', 'other' => 'ok'])->andReturn($component);
        $__env->shouldReceive('startComponent');
        $__env->shouldReceive('renderComponent');

        $template = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile {{ $attributes }} />');
        $template = $this->compiler->compileString($template);

        ob_start();
        eval(" ?> $template <?php ");
        ob_get_clean();

        $this->assertSame($attributes->get('userId'), null);
        $this->assertSame($attributes->get('other'), 'ok');
    }

    protected function mockViewFactory($existsSucceeds = true)
    {
        $container = new Container;
        $container->instance(Factory::class, $factory = Mockery::mock(Factory::class));
        $factory->shouldReceive('exists')->andReturn($existsSucceeds);
        Container::setInstance($container);
    }

    protected function compiler($aliases = [])
    {
        return new ComponentTagCompiler(
            $aliases
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
