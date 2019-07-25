<?php

namespace Illuminate\Tests\View\Blade;

class BladeIncludesTest extends AbstractBladeTestCase
{
    public function testEachsAreCompiled()
    {
        $this->assertEquals('<?php echo $__env->renderEach(\'foo\', \'bar\'); ?>', $this->compiler->compileString('@each(\'foo\', \'bar\')'));
        $this->assertEquals('<?php echo $__env->renderEach(name(foo)); ?>', $this->compiler->compileString('@each(name(foo))'));
    }

    public function testIncludesAreCompiled()
    {
        $this->assertEquals('<?php echo $__env->make(\'foo\', \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>', $this->compiler->compileString('@include(\'foo\')'));
        $this->assertEquals('<?php echo $__env->make(name(foo), \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>', $this->compiler->compileString('@include(name(foo))'));
    }

    public function testIncludeIfsAreCompiled()
    {
        $this->assertEquals('<?php if ($__env->exists(\'foo\')) echo $__env->make(\'foo\', \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>', $this->compiler->compileString('@includeIf(\'foo\')'));
        $this->assertEquals('<?php if ($__env->exists(name(foo))) echo $__env->make(name(foo), \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>', $this->compiler->compileString('@includeIf(name(foo))'));
    }

    public function testIncludeWhensAreCompiled()
    {
        $this->assertEquals('<?php echo $__env->renderWhen(true, \'foo\', ["foo" => "bar"], \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeWhen(true, \'foo\', ["foo" => "bar"])'));
        $this->assertEquals('<?php echo $__env->renderWhen(true, \'foo\', \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\'])); ?>', $this->compiler->compileString('@includeWhen(true, \'foo\')'));
    }

    public function testIncludeFirstsAreCompiled()
    {
        $this->assertEquals('<?php echo $__env->first(["one", "two"], \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>', $this->compiler->compileString('@includeFirst(["one", "two"])'));
        $this->assertEquals('<?php echo $__env->first(["one", "two"], ["foo" => "bar"], \Illuminate\Support\Arr::except(get_defined_vars(), [\'__data\', \'__path\']))->render(); ?>', $this->compiler->compileString('@includeFirst(["one", "two"], ["foo" => "bar"])'));
    }
}
