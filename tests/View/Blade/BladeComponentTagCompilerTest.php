<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\ComponentTagCompiler;
use Illuminate\View\Component;
use InvalidArgumentException;
use Mockery;

class BladeComponentTagCompilerTest extends AbstractBladeTestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testSlotsCanBeCompiled()
    {
        $result = (new ComponentTagCompiler)->compileSlots('<x-slot name="foo">
</x-slot>');

        $this->assertSame("@slot('foo') \n".' @endslot', trim($result));
    }

    public function testBasicComponentParsing()
    {
        $this->mockViewFactory();

        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<div><x-alert type="foo" limit="5" @click="foo" required /><x-alert /></div>');

        $this->assertSame("<div> @component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
<?php \$component->withName('alert'); ?>
<?php \$component->withAttributes(['type' => 'foo','limit' => '5','@click' => 'foo','required' => true]); ?>\n".
"@endcomponentClass  @component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
<?php \$component->withName('alert'); ?>
<?php \$component->withAttributes([]); ?>\n".
'@endcomponentClass </div>', trim($result));
    }

    public function testBasicComponentWithEmptyAttributesParsing()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<div><x-alert type="" limit=\'\' @click="" required /></div>');

        $this->assertSame("<div> @component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
<?php \$component->withName('alert'); ?>
<?php \$component->withAttributes(['type' => '','limit' => '','@click' => '','required' => true]); ?>\n".
'@endcomponentClass </div>', trim($result));
    }

    public function testDataCamelCasing()
    {
        $result = (new ComponentTagCompiler(['profile' => TestProfileComponent::class]))->compileTags('<x-profile user-id="1"></x-profile>');

        $this->assertSame("@component('Illuminate\Tests\View\Blade\TestProfileComponent', ['userId' => '1'])
<?php \$component->withName('profile'); ?>
<?php \$component->withAttributes([]); ?> @endcomponentClass", trim($result));
    }

    public function testColonData()
    {
        $result = (new ComponentTagCompiler(['profile' => TestProfileComponent::class]))->compileTags('<x-profile :user-id="1"></x-profile>');

        $this->assertSame("@component('Illuminate\Tests\View\Blade\TestProfileComponent', ['userId' => 1])
<?php \$component->withName('profile'); ?>
<?php \$component->withAttributes([]); ?> @endcomponentClass", trim($result));
    }

    public function testColonAttributesIsEscapedIfStrings()
    {
        $result = (new ComponentTagCompiler(['profile' => TestProfileComponent::class]))->compileTags('<x-profile :src="\'foo\'"></x-profile>');

        $this->assertSame("@component('Illuminate\Tests\View\Blade\TestProfileComponent', [])
<?php \$component->withName('profile'); ?>
<?php \$component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('foo')]); ?> @endcomponentClass", trim($result));
    }

    public function testColonNestedComponentParsing()
    {
        $result = (new ComponentTagCompiler(['foo:alert' => TestAlertComponent::class]))->compileTags('<x-foo:alert></x-foo:alert>');

        $this->assertSame("@component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
<?php \$component->withName('foo:alert'); ?>
<?php \$component->withAttributes([]); ?> @endcomponentClass", trim($result));
    }

    public function testColonStartingNestedComponentParsing()
    {
        $result = (new ComponentTagCompiler(['foo:alert' => TestAlertComponent::class]))->compileTags('<x:foo:alert></x-foo:alert>');

        $this->assertSame("@component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
<?php \$component->withName('foo:alert'); ?>
<?php \$component->withAttributes([]); ?> @endcomponentClass", trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiled()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<div><x-alert/></div>');

        $this->assertSame("<div> @component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
<?php \$component->withName('alert'); ?>
<?php \$component->withAttributes([]); ?>\n".
'@endcomponentClass </div>', trim($result));
    }

    public function testClassNamesCanBeGuessed()
    {
        $container = new Container;
        $container->instance(Application::class, $app = Mockery::mock(Application::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        Container::setInstance($container);

        $result = (new ComponentTagCompiler([]))->guessClassName('alert');

        $this->assertSame("App\View\Components\Alert", trim($result));

        Container::setInstance(null);
    }

    public function testClassNamesCanBeGuessedWithNamespaces()
    {
        $container = new Container;
        $container->instance(Application::class, $app = Mockery::mock(Application::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        Container::setInstance($container);

        $result = (new ComponentTagCompiler([]))->guessClassName('base.alert');

        $this->assertSame("App\View\Components\Base\Alert", trim($result));

        Container::setInstance(null);
    }

    public function testComponentsCanBeCompiledWithHyphenAttributes()
    {
        $this->mockViewFactory();

        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<x-alert class="bar" wire:model="foo" x-on:click="bar" @click="baz" />');

        $this->assertSame("@component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
<?php \$component->withName('alert'); ?>
<?php \$component->withAttributes(['class' => 'bar','wire:model' => 'foo','x-on:click' => 'bar','@click' => 'baz']); ?>\n".
'@endcomponentClass', trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithDataAndAttributes()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<x-alert title="foo" class="bar" wire:model="foo" />');

        $this->assertSame("@component('Illuminate\Tests\View\Blade\TestAlertComponent', ['title' => 'foo'])
<?php \$component->withName('alert'); ?>
<?php \$component->withAttributes(['class' => 'bar','wire:model' => 'foo']); ?>\n".
'@endcomponentClass', trim($result));
    }

    public function testComponentsCanHaveAttachedWord()
    {
        $result = (new ComponentTagCompiler(['profile' => TestProfileComponent::class]))->compileTags('<x-profile></x-profile>Words');

        $this->assertSame("@component('Illuminate\Tests\View\Blade\TestProfileComponent', [])
<?php \$component->withName('profile'); ?>
<?php \$component->withAttributes([]); ?> @endcomponentClass Words", trim($result));
    }

    public function testSelfClosingComponentsCanHaveAttachedWord()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<x-alert/>Words');

        $this->assertSame("@component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
<?php \$component->withName('alert'); ?>
<?php \$component->withAttributes([]); ?>\n".
'@endcomponentClass Words', trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithBoundData()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<x-alert :title="$title" class="bar" />');

        $this->assertSame("@component('Illuminate\Tests\View\Blade\TestAlertComponent', ['title' => \$title])
<?php \$component->withName('alert'); ?>
<?php \$component->withAttributes(['class' => 'bar']); ?>\n".
'@endcomponentClass', trim($result));
    }

    public function testPairedComponentTags()
    {
        $result = (new ComponentTagCompiler(['alert' => TestAlertComponent::class]))->compileTags('<x-alert>
</x-alert>');

        $this->assertSame("@component('Illuminate\Tests\View\Blade\TestAlertComponent', [])
<?php \$component->withName('alert'); ?>
<?php \$component->withAttributes([]); ?>
 @endcomponentClass", trim($result));
    }

    public function testClasslessComponents()
    {
        $container = new Container;
        $container->instance(Application::class, $app = Mockery::mock(Application::class));
        $container->instance(Factory::class, $factory = Mockery::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(true);
        Container::setInstance($container);

        $result = (new ComponentTagCompiler([]))->compileTags('<x-anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("@component('Illuminate\View\AnonymousComponent', ['view' => 'components.anonymous-component','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php \$component->withName('anonymous-component'); ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
'@endcomponentClass', trim($result));
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

        (new ComponentTagCompiler(['alert' => 'foo.bar']))->compileTags('<x-alert />');
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

        (new ComponentTagCompiler)->compileTags('<x-alert />');
    }

    protected function mockViewFactory($existsSucceeds = true)
    {
        $container = new Container;
        $container->instance(Factory::class, $factory = Mockery::mock(Factory::class));
        $factory->shouldReceive('exists')->andReturn($existsSucceeds);
        Container::setInstance($container);
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
