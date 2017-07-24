<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeCommentsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCommentsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '{{--this is a comment--}}';
        $this->assertEmpty($compiler->compileString($string));

        $string = '{{--
this is a comment
--}}';
        $this->assertEmpty($compiler->compileString($string));

        $string = sprintf('{{-- this is an %s long comment --}}', str_repeat('extremely ', 1000));
        $this->assertEmpty($compiler->compileString($string));
    }

    public function testBladeCodeInsideCommentsIsNotCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '{{-- @foreach() --}}';

        $this->assertEmpty($compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
