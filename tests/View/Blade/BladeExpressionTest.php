<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeExpressionTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testExpressionsOnTheSameLine()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo app(\'translator\')->getFromJson(foo(bar(baz(qux(breeze()))))); ?> space () <?php echo app(\'translator\')->getFromJson(foo(bar)); ?>', $compiler->compileString('@lang(foo(bar(baz(qux(breeze()))))) space () @lang(foo(bar))'));
    }

    public function testExpressionWithinHTML()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<html <?php echo e($foo); ?>>', $compiler->compileString('<html {{ $foo }}>'));
        $this->assertEquals('<html<?php echo e($foo); ?>>', $compiler->compileString('<html{{ $foo }}>'));
        $this->assertEquals('<html <?php echo e($foo); ?> <?php echo app(\'translator\')->getFromJson(\'foo\'); ?>>', $compiler->compileString('<html {{ $foo }} @lang(\'foo\')>'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
