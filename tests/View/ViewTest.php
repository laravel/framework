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
use Mockery;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
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
        $view->getFactory()->expects('incrementRender')->ordered();
        $view->getFactory()->expects('callComposer')->ordered()->with($view);
        $view->getFactory()->expects('getShared')->andReturn(['shared' => 'foo']);
        $view->getEngine()->expects('get')->with('path', ['foo' => 'bar', 'shared' => 'foo'])->andReturn('contents');
        $view->getFactory()->expects('decrementRender')->ordered();
        $view->getFactory()->expects('flushStateIfDoneRendering');

        $callback = function (View $rendered, $contents) use ($view) {
            $this->assertEquals($view, $rendered);
            $this->assertSame('contents', $contents);
        };

        $this->assertSame('contents', $view->render($callback));
    }

    public function testRenderHandlingCallbackReturnValues()
    {
        $view = $this->getView();
        $view->getFactory()->expects('incrementRender')->times(3);
        $view->getFactory()->expects('callComposer')->times(3);
        $view->getFactory()->expects('getShared')->times(3)->andReturn(['shared' => 'foo']);
        $view->getEngine()->expects('get')->times(3)->andReturn('contents');
        $view->getFactory()->expects('decrementRender')->times(3);
        $view->getFactory()->expects('flushStateIfDoneRendering')->times(3);

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
        $view = Mockery::mock(View::class.'[render]', [
            Mockery::mock(Factory::class),
            Mockery::mock(Engine::class),
            'view',
            'path',
            [],
        ]);

        $view->expects('render')->with(Mockery::type(Closure::class))->andReturn($sections = ['foo' => 'bar']);

        $this->assertEquals($sections, $view->renderSections());
    }

    public function testSectionsAreNotFlushedWhenNotDoneRendering()
    {
        $view = $this->getView(['foo' => 'bar']);
        $view->getFactory()->expects('incrementRender')->times(2);
        $view->getFactory()->expects('callComposer')->times(2)->with($view);
        $view->getFactory()->expects('getShared')->times(2)->andReturn(['shared' => 'foo']);
        $view->getEngine()->expects('get')->times(2)->with('path', ['foo' => 'bar', 'shared' => 'foo'])->andReturn('contents');
        $view->getFactory()->expects('decrementRender')->times(2);
        $view->getFactory()->expects('flushStateIfDoneRendering')->times(2);

        $this->assertSame('contents', $view->render());
        $this->assertSame('contents', (string) $view);
    }

    public function testViewNestBindsASubView()
    {
        $view = $this->getView();
        $view->getFactory()->expects('make')->with('foo', ['data']);
        $result = $view->nest('key', 'foo', ['data']);

        $this->assertInstanceOf(View::class, $result);
    }

    public function testViewAcceptsArrayableImplementations()
    {
        $arrayable = Mockery::mock(Arrayable::class);
        $arrayable->expects('toArray')->andReturn(['foo' => 'bar', 'baz' => ['qux', 'corge']]);

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
        $this->expectExceptionObject(new BadMethodCallException('Method Illuminate\View\View::badMethodCall does not exist.'));

        $view = $this->getView();
        $view->badMethodCall();
    }

    public function testViewGatherDataWithRenderable()
    {
        $view = $this->getView();
        $view->getFactory()->expects('incrementRender')->ordered();
        $view->getFactory()->expects('callComposer')->ordered()->with($view);
        $view->getFactory()->expects('getShared')->andReturn(['shared' => 'foo']);
        $view->getEngine()->expects('get')->andReturn('contents');
        $view->getFactory()->expects('decrementRender')->ordered();
        $view->getFactory()->expects('flushStateIfDoneRendering');

        $view->renderable = Mockery::mock(Renderable::class);
        $view->renderable->expects('render')->andReturn('text');
        $this->assertSame('contents', $view->render());
    }

    public function testViewRenderSections()
    {
        $view = $this->getView();
        $view->getFactory()->expects('incrementRender')->ordered();
        $view->getFactory()->expects('callComposer')->ordered()->with($view);
        $view->getFactory()->expects('getShared')->andReturn(['shared' => 'foo']);
        $view->getEngine()->expects('get')->andReturn('contents');
        $view->getFactory()->expects('decrementRender')->ordered();
        $view->getFactory()->expects('flushStateIfDoneRendering');

        $view->getFactory()->expects('getSections')->andReturn(['foo', 'bar']);
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
            Mockery::mock(Factory::class),
            Mockery::mock(Engine::class),
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
