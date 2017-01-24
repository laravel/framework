<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeForelseStatementsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testForelseStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@forelse ($this->getUsers() as $user)
breeze
@empty
empty
@endforelse';
        $expected = '<?php $__empty_1 = true; $__currentLoopData = $this->getUsers(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
breeze
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
empty
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testForelseStatementsAreCompiledWithUppercaseSyntax()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@forelse ($this->getUsers() AS $user)
breeze
@empty
empty
@endforelse';
        $expected = '<?php $__empty_1 = true; $__currentLoopData = $this->getUsers(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
breeze
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
empty
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testForelseStatementsAreCompiledWithMultipleLine()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@forelse ([
foo,
bar,
] as $label)
breeze
@empty
empty
@endforelse';
        $expected = '<?php $__empty_1 = true; $__currentLoopData = [
foo,
bar,
]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
breeze
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
empty
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testNestedForelseStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@forelse ($this->getUsers() as $user)
@forelse ($user->tags as $tag)
breeze
@empty
tag empty
@endforelse
@empty
empty
@endforelse';
        $expected = '<?php $__empty_1 = true; $__currentLoopData = $this->getUsers(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
<?php $__empty_2 = true; $__currentLoopData = $user->tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
breeze
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
tag empty
<?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
empty
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
