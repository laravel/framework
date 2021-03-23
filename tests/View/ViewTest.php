<?php

namespace Illuminate\Tests\View;

use ArrayAccess;
use BadMethodCallException;
use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Engine;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Factory;
use Illuminate\View\View;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testDataCanBeSetOnView()
    {
        $view = $this->getView();
        $view->with('foo', 'bar');
        $view->with(['baz' => 'boom']);
        $this->assertEquals(['foo' => 'bar', 'baz' => 'boom'], $view->getData());

        $view = $this->getView();
        $view->withFoo('bar')->withBaz('boom');
        $this->assertEquals(['foo' => 'bar', 'baz' => 'boom'], $view->getData());
    }

    public function testRenderProperlyRendersView()
    {
        $view = $this->getView(['foo' => 'bar']);
        $view->getFactory()->shouldReceive('incrementRender')->once()->ordered();
        $view->getFactory()->shouldReceive('callComposer')->once()->ordered()->with($view);
        $view->getFactory()->shouldReceive('getShared')->once()->andReturn(['shared' => 'foo']);
        $view->getEngine()->shouldReceive('get')->once()->with('path', ['foo' => 'bar', 'shared' => 'foo'])->andReturn('contents');
        $view->getFactory()->shouldReceive('decrementRender')->once()->ordered();
        $view->getFactory()->shouldReceive('flushStateIfDoneRendering')->once();

        $callback = function (View $rendered, $contents) use ($view) {
            $this->assertEquals($view, $rendered);
            $this->assertSame('contents', $contents);
        };

        $this->assertSame('contents', $view->render($callback));
    }

    public function testRenderHandlingCallbackReturnValues()
    {
        $view = $this->getView();
        $view->getFactory()->shouldReceive('incrementRender');
        $view->getFactory()->shouldReceive('callComposer');
        $view->getFactory()->shouldReceive('getShared')->andReturn(['shared' => 'foo']);
        $view->getEngine()->shouldReceive('get')->andReturn('contents');
        $view->getFactory()->shouldReceive('decrementRender');
        $view->getFactory()->shouldReceive('flushStateIfDoneRendering');

        $this->assertSame('new contents', $view->render(function () {
            return 'new contents';
        }));

        $this->assertEmpty($view->render(function () {
            return '';
        }));

        $this->assertSame('contents', $view->render(function () {
            //
        }));
    }

    public function testRenderSectionsReturnsEnvironmentSections()
    {
        $view = m::mock(View::class.'[render]', [
            m::mock(Factory::class),
            m::mock(Engine::class),
            'view',
            'path',
            [],
        ]);

        $view->shouldReceive('render')->with(m::type(Closure::class))->once()->andReturn($sections = ['foo' => 'bar']);

        $this->assertEquals($sections, $view->renderSections());
    }

    public function testSectionsAreNotFlushedWhenNotDoneRendering()
    {
        $view = $this->getView(['foo' => 'bar']);
        $view->getFactory()->shouldReceive('incrementRender')->twice();
        $view->getFactory()->shouldReceive('callComposer')->twice()->with($view);
        $view->getFactory()->shouldReceive('getShared')->twice()->andReturn(['shared' => 'foo']);
        $view->getEngine()->shouldReceive('get')->twice()->with('path', ['foo' => 'bar', 'shared' => 'foo'])->andReturn('contents');
        $view->getFactory()->shouldReceive('decrementRender')->twice();
        $view->getFactory()->shouldReceive('flushStateIfDoneRendering')->twice();

        $this->assertSame('contents', $view->render());
        $this->assertSame('contents', (string) $view);
    }

    public function testViewNestBindsASubView()
    {
        $view = $this->getView();
        $view->getFactory()->shouldReceive('make')->once()->with('foo', ['data']);
        $result = $view->nest('key', 'foo', ['data']);

        $this->assertInstanceOf(View::class, $result);
    }

    public function testViewAcceptsArrayableImplementations()
    {
        $arrayable = m::mock(Arrayable::class);
        $arrayable->shouldReceive('toArray')->once()->andReturn(['foo' => 'bar', 'baz' => ['qux', 'corge']]);

        $view = $this->getView($arrayable);

        $this->assertSame('bar', $view->foo);
        $this->assertEquals(['qux', 'corge'], $view->baz);
    }

    public function testViewGettersSetters()
    {
        $view = $this->getView(['foo' => 'bar']);
        $this->assertSame('view', $view->name());
        $this->assertSame('path', $view->getPath());
        $data = $view->getData();
        $this->assertSame('bar', $data['foo']);
        $view->setPath('newPath');
        $this->assertSame('newPath', $view->getPath());
    }

    public function testViewArrayAccess()
    {
        $view = $this->getView(['foo' => 'bar']);
        $this->assertInstanceOf(ArrayAccess::class, $view);
        $this->assertTrue($view->offsetExists('foo'));
        $this->assertSame('bar', $view->offsetGet('foo'));
        $view->offsetSet('foo', 'baz');
        $this->assertSame('baz', $view->offsetGet('foo'));
        $view->offsetUnset('foo');
        $this->assertFalse($view->offsetExists('foo'));
    }

    public function testViewConstructedWithObjectData()
    {
        $view = $this->getView(new DataObjectStub);
        $this->assertInstanceOf(ArrayAccess::class, $view);
        $this->assertTrue($view->offsetExists('foo'));
        $this->assertSame('bar', $view->offsetGet('foo'));
        $view->offsetSet('foo', 'baz');
        $this->assertSame('baz', $view->offsetGet('foo'));
        $view->offsetUnset('foo');
        $this->assertFalse($view->offsetExists('foo'));
    }

    public function testViewMagicMethods()
    {
        $view = $this->getView(['foo' => 'bar']);
        $this->assertTrue(isset($view->foo));
        $this->assertSame('bar', $view->foo);
        $view->foo = 'baz';
        $this->assertSame('baz', $view->foo);
        $this->assertEquals($view['foo'], $view->foo);
        unset($view->foo);
        $this->assertFalse(isset($view->foo));
        $this->assertFalse($view->offsetExists('foo'));
    }

    public function testViewBadMethod()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method Illuminate\View\View::badMethodCall does not exist.');

        $view = $this->getView();
        $view->badMethodCall();
    }

    public function testViewGatherDataWithRenderable()
    {
        $view = $this->getView();
        $view->getFactory()->shouldReceive('incrementRender')->once()->ordered();
        $view->getFactory()->shouldReceive('callComposer')->once()->ordered()->with($view);
        $view->getFactory()->shouldReceive('getShared')->once()->andReturn(['shared' => 'foo']);
        $view->getEngine()->shouldReceive('get')->once()->andReturn('contents');
        $view->getFactory()->shouldReceive('decrementRender')->once()->ordered();
        $view->getFactory()->shouldReceive('flushStateIfDoneRendering')->once();

        $view->renderable = m::mock(Renderable::class);
        $view->renderable->shouldReceive('render')->once()->andReturn('text');
        $this->assertSame('contents', $view->render());
    }

    public function testViewRenderSections()
    {
        $view = $this->getView();
        $view->getFactory()->shouldReceive('incrementRender')->once()->ordered();
        $view->getFactory()->shouldReceive('callComposer')->once()->ordered()->with($view);
        $view->getFactory()->shouldReceive('getShared')->once()->andReturn(['shared' => 'foo']);
        $view->getEngine()->shouldReceive('get')->once()->andReturn('contents');
        $view->getFactory()->shouldReceive('decrementRender')->once()->ordered();
        $view->getFactory()->shouldReceive('flushStateIfDoneRendering')->once();

        $view->getFactory()->shouldReceive('getSections')->once()->andReturn(['foo', 'bar']);
        $sections = $view->renderSections();
        $this->assertSame('foo', $sections[0]);
        $this->assertSame('bar', $sections[1]);
    }

    public function testWithErrors()
    {
        $view = $this->getView();
        $errors = ['foo' => 'bar', 'qu' => 'ux'];
        $this->assertSame($view, $view->withErrors($errors));
        $this->assertInstanceOf(ViewErrorBag::class, $view->errors);
        $foo = $view->errors->get('foo');
        $this->assertSame('bar', $foo[0]);
        $qu = $view->errors->get('qu');
        $this->assertSame('ux', $qu[0]);
        $data = ['foo' => 'baz'];
        $this->assertSame($view, $view->withErrors(new MessageBag($data)));
        $foo = $view->errors->get('foo');
        $this->assertSame('baz', $foo[0]);
        $foo = $view->errors->getBag('default')->get('foo');
        $this->assertSame('baz', $foo[0]);
        $this->assertSame($view, $view->withErrors(new MessageBag($data), 'login'));
        $foo = $view->errors->getBag('login')->get('foo');
        $this->assertSame('baz', $foo[0]);
    }

    protected function getView($data = [])
    {
        return new View(
            m::mock(Factory::class),
            m::mock(Engine::class),
            'view',
            'path',
            $data
        );
    }
}

class DataObjectStub
{
    public $foo = 'bar';
}
