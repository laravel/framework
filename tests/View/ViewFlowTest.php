<?php

use Mockery as m;
use Illuminate\View\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Compilers\BladeCompiler;

class ViewFlowTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $files = new Filesystem;
        $this->tempDir = __DIR__.'/tmp';

        if (!$files->exists($this->tempDir)) {
            $files->makeDirectory($this->tempDir);
        }
    }

    public function tearDown()
    {
        $files = new Filesystem;
        $files->deleteDirectory($this->tempDir);

        m::close();
    }

    public function testPushWithExtend()
    {
        $files = new Filesystem;
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);

        $files->put($this->tempDir.'/child.php', $compiler->compileString('
@extends("layout")

@push("content")
World
@endpush'));
        $files->put($this->tempDir.'/layout.php', $compiler->compileString('
@push("content")
Hello
@endpush
@stack("content")'));

        $factory = $this->prepareCommonFactory();
        $this->assertEquals("Hello\nWorld\n", $factory->make('child')->render());
    }

    public function testPushWithMultipleExtends()
    {
        $files = new Filesystem;
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);

        $files->put($this->tempDir.'/a.php', $compiler->compileString('
a
@stack("me")'));

        $files->put($this->tempDir.'/b.php', $compiler->compileString('
@extends("a")
@push("me")
b
@endpush'));

        $files->put($this->tempDir.'/c.php', $compiler->compileString('
@extends("b")
@push("me")
c
@endpush'));

        $factory = $this->prepareCommonFactory();
        $this->assertEquals("a\nb\nc\n", $factory->make('c')->render());
    }

    public function testPushWithInputAndExtend()
    {
        $files = new Filesystem;
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);

        $files->put($this->tempDir.'/aa.php', $compiler->compileString('
a
@stack("me")'));

        $files->put($this->tempDir.'/bb.php', $compiler->compileString('
@push("me")
b
@endpush'));

        $files->put($this->tempDir.'/cc.php', $compiler->compileString('
@extends("aa")
@include("bb")
@push("me")
c
@endpush'));

        $factory = $this->prepareCommonFactory();
        $this->assertEquals("a\nc\nb\n", $factory->make('cc')->render());
    }

    public function testExtends()
    {
        $files = new Filesystem;
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);

        $files->put($this->tempDir.'/extends-a.php', $compiler->compileString('
yield:
@yield("me")'));

        $files->put($this->tempDir.'/extends-b.php', $compiler->compileString('
@extends("extends-a")
@section("me")
b
@endsection'));

        $files->put($this->tempDir.'/extends-c.php', $compiler->compileString('
@extends("extends-b")
@section("me")
c
@endsection'));

        $factory = $this->prepareCommonFactory();
        $this->assertEquals("yield:\nb\n", $factory->make('extends-b')->render());
        $this->assertEquals("yield:\nc\n", $factory->make('extends-c')->render());
    }

    public function testExtendsWithParent()
    {
        $files = new Filesystem;
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);

        $files->put($this->tempDir.'/extends-layout.php', $compiler->compileString('
yield:
@yield("me")'));

        $files->put($this->tempDir.'/extends-dad.php', $compiler->compileString('
@extends("extends-layout")
@section("me")
dad
@endsection'));

        $files->put($this->tempDir.'/extends-child.php', $compiler->compileString('
@extends("extends-dad")
@section("me")
@parent
child
@endsection'));

        $factory = $this->prepareCommonFactory();
        $this->assertEquals("yield:\ndad\n\nchild\n", $factory->make('extends-child')->render());
    }

    public function testExtendsWithVariable()
    {
        $files = new Filesystem;
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);

        $files->put($this->tempDir.'/extends-variable-layout.php', $compiler->compileString('
yield:
@yield("me")'));

        $files->put($this->tempDir.'/extends-variable-dad.php', $compiler->compileString('
@extends("extends-variable-layout")
@section("me")
dad
@endsection'));

        $files->put($this->tempDir.'/extends-variable-child-a.php', $compiler->compileString('
@extends("extends-variable-dad")
@section("me")
{{ $title }}
@endsection'));

        $files->put($this->tempDir.'/extends-variable-child-b.php', $compiler->compileString('
@extends("extends-variable-dad")
@section("me")
{{ $title }}
@endsection'));

        $factory = $this->prepareCommonFactory();
        $this->assertEquals("yield:\ntitle\n", $factory->make('extends-variable-child-a', ['title' => 'title'])->render());
        $this->assertEquals("yield:\ndad\n\n", $factory->make('extends-variable-child-b', ['title' => '@parent'])->render());
    }

    protected function prepareCommonFactory()
    {
        $engine = new CompilerEngine(m::mock('Illuminate\View\Compilers\CompilerInterface'));
        $engine->getCompiler()->shouldReceive('getCompiledPath')
            ->andReturnUsing(function ($path) { return $path; });
        $engine->getCompiler()->shouldReceive('isExpired')->andReturn(false);

        $factory = $this->getFactory();
        $factory->getEngineResolver()->shouldReceive('resolve')->andReturn($engine);
        $factory->getFinder()->shouldReceive('find')->andReturnUsing(function ($path) {
            return $this->tempDir.'/'.$path.'.php';
        });
        $factory->getDispatcher()->shouldReceive('fire');

        return $factory;
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
