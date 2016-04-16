<?php

use Mockery as m;
use Illuminate\View\Factory;
use Illuminate\View\Compilers\BladeCompiler;

class ViewFlowTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testPushWithExtend()
    {
        $files = new Illuminate\Filesystem\Filesystem;

        $compiler = new BladeCompiler($this->getFiles(), __DIR__);

        $files->put(__DIR__.'/fixtures/child.php', $compiler->compileString('
@extends("layout")

@push("content")
World
@endpush'));
        $files->put(__DIR__.'/fixtures/layout.php', $compiler->compileString('
@push("content")
Hello
@endpush
@stack("content")'));

        $engine = new Illuminate\View\Engines\CompilerEngine(m::mock('Illuminate\View\Compilers\CompilerInterface'));
        $engine->getCompiler()->shouldReceive('getCompiledPath')->andReturnUsing(function ($path) { return $path; });
        $engine->getCompiler()->shouldReceive('isExpired')->times(2)->andReturn(false);

        $factory = $this->getFactory();
        $factory->getEngineResolver()->shouldReceive('resolve')->times(2)->andReturn($engine);
        $factory->getFinder()->shouldReceive('find')->once()->with('child')->andReturn(__DIR__.'/fixtures/child.php');
        $factory->getFinder()->shouldReceive('find')->once()->with('layout')->andReturn(__DIR__.'/fixtures/layout.php');
        $factory->getDispatcher()->shouldReceive('fire')->times(4);

        $this->assertEquals("Hello\nWorld\n", $factory->make('child')->render());

        $files->delete(__DIR__.'/fixtures/layout.php');
        $files->delete(__DIR__.'/fixtures/child.php');
    }

    public function testPushWithMultipleExtends()
    {
        $files = new Illuminate\Filesystem\Filesystem;

        $compiler = new BladeCompiler($this->getFiles(), __DIR__);

        $files->put(__DIR__.'/fixtures/a.php', $compiler->compileString('
a
@stack("me")'));

        $files->put(__DIR__.'/fixtures/b.php', $compiler->compileString('
@extends("a")
@push("me")
b
@endpush("me")'));

        $files->put(__DIR__.'/fixtures/c.php', $compiler->compileString('
@extends("b")
@push("me")
c
@endpush("me")'));

        $engine = new Illuminate\View\Engines\CompilerEngine(m::mock('Illuminate\View\Compilers\CompilerInterface'));
        $engine->getCompiler()->shouldReceive('getCompiledPath')->andReturnUsing(function ($path) { return $path; });
        $engine->getCompiler()->shouldReceive('isExpired')->andReturn(false);

        $factory = $this->getFactory();
        $factory->getEngineResolver()->shouldReceive('resolve')->andReturn($engine);
        $factory->getFinder()->shouldReceive('find')->andReturnUsing(function ($path) {
            return __DIR__.'/fixtures/'.$path.'.php';
        });
        $factory->getDispatcher()->shouldReceive('fire');

        $this->assertEquals("a\nb\nc\n", $factory->make('c')->render());

        $files->delete(__DIR__.'/fixtures/a.php');
        $files->delete(__DIR__.'/fixtures/b.php');
        $files->delete(__DIR__.'/fixtures/c.php');
    }

    public function testPushWithInputAndExtend()
    {
        $files = new Illuminate\Filesystem\Filesystem;

        $compiler = new BladeCompiler($this->getFiles(), __DIR__);

        $files->put(__DIR__.'/fixtures/aa.php', $compiler->compileString('
a
@stack("me")'));

        $files->put(__DIR__.'/fixtures/bb.php', $compiler->compileString('
@push("me")
b
@endpush("me")'));

        $files->put(__DIR__.'/fixtures/cc.php', $compiler->compileString('
@extends("aa")
@include("bb")
@push("me")
c
@endpush("me")'));

        $engine = new Illuminate\View\Engines\CompilerEngine(m::mock('Illuminate\View\Compilers\CompilerInterface'));
        $engine->getCompiler()->shouldReceive('getCompiledPath')->andReturnUsing(function ($path) { return $path; });
        $engine->getCompiler()->shouldReceive('isExpired')->andReturn(false);

        $factory = $this->getFactory();
        $factory->getEngineResolver()->shouldReceive('resolve')->andReturn($engine);
        $factory->getFinder()->shouldReceive('find')->andReturnUsing(function ($path) {
            return __DIR__.'/fixtures/'.$path.'.php';
        });
        $factory->getDispatcher()->shouldReceive('fire');

        $this->assertEquals("a\nc\nb\n", $factory->make('cc')->render());

        $files->delete(__DIR__.'/fixtures/aa.php');
        $files->delete(__DIR__.'/fixtures/bb.php');
        $files->delete(__DIR__.'/fixtures/cc.php');
    }

    protected function getFactory()
    {
        return new Factory(
            m::mock('Illuminate\View\Engines\EngineResolver'),
            m::mock('Illuminate\View\ViewFinderInterface'),
            m::mock('Illuminate\Contracts\Events\Dispatcher')
        );
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
