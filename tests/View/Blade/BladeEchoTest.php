<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeEchoTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testEchosAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);

        $this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{!!$name!!}'));
        $this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{!! $name !!}'));
        $this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{!!
            $name
        !!}'));
        $this->assertEquals('<?php echo isset($name) ? $name : \'foo\'; ?>',
            $compiler->compileString('{!! $name or \'foo\' !!}'));

        $this->assertEquals('<?php echo e($name); ?>', $compiler->compileString('{{{$name}}}'));
        $this->assertEquals('<?php echo e($name); ?>', $compiler->compileString('{{$name}}'));
        $this->assertEquals('<?php echo e($name); ?>', $compiler->compileString('{{ $name }}'));
        $this->assertEquals('<?php echo e($name); ?>', $compiler->compileString('{{
            $name
        }}'));
        $this->assertEquals("<?php echo e(\$name); ?>\n\n", $compiler->compileString("{{ \$name }}\n"));
        $this->assertEquals("<?php echo e(\$name); ?>\r\n\r\n", $compiler->compileString("{{ \$name }}\r\n"));
        $this->assertEquals("<?php echo e(\$name); ?>\n\n", $compiler->compileString("{{ \$name }}\n"));
        $this->assertEquals("<?php echo e(\$name); ?>\r\n\r\n", $compiler->compileString("{{ \$name }}\r\n"));

        $this->assertEquals('<?php echo e(isset($name) ? $name : "foo"); ?>',
            $compiler->compileString('{{ $name or "foo" }}'));
        $this->assertEquals('<?php echo e(isset($user->name) ? $user->name : "foo"); ?>',
            $compiler->compileString('{{ $user->name or "foo" }}'));
        $this->assertEquals('<?php echo e(isset($name) ? $name : "foo"); ?>',
            $compiler->compileString('{{$name or "foo"}}'));
        $this->assertEquals('<?php echo e(isset($name) ? $name : "foo"); ?>', $compiler->compileString('{{
            $name or "foo"
        }}'));

        $this->assertEquals('<?php echo e(isset($name) ? $name : \'foo\'); ?>',
            $compiler->compileString('{{ $name or \'foo\' }}'));
        $this->assertEquals('<?php echo e(isset($name) ? $name : \'foo\'); ?>',
            $compiler->compileString('{{$name or \'foo\'}}'));
        $this->assertEquals('<?php echo e(isset($name) ? $name : \'foo\'); ?>', $compiler->compileString('{{
            $name or \'foo\'
        }}'));

        $this->assertEquals('<?php echo e(isset($age) ? $age : 90); ?>', $compiler->compileString('{{ $age or 90 }}'));
        $this->assertEquals('<?php echo e(isset($age) ? $age : 90); ?>', $compiler->compileString('{{$age or 90}}'));
        $this->assertEquals('<?php echo e(isset($age) ? $age : 90); ?>', $compiler->compileString('{{
            $age or 90
        }}'));

        $this->assertEquals('<?php echo e("Hello world or foo"); ?>',
            $compiler->compileString('{{ "Hello world or foo" }}'));
        $this->assertEquals('<?php echo e("Hello world or foo"); ?>',
            $compiler->compileString('{{"Hello world or foo"}}'));
        $this->assertEquals('<?php echo e($foo + $or + $baz); ?>', $compiler->compileString('{{$foo + $or + $baz}}'));
        $this->assertEquals('<?php echo e("Hello world or foo"); ?>', $compiler->compileString('{{
            "Hello world or foo"
        }}'));

        $this->assertEquals('<?php echo e(\'Hello world or foo\'); ?>',
            $compiler->compileString('{{ \'Hello world or foo\' }}'));
        $this->assertEquals('<?php echo e(\'Hello world or foo\'); ?>',
            $compiler->compileString('{{\'Hello world or foo\'}}'));
        $this->assertEquals('<?php echo e(\'Hello world or foo\'); ?>', $compiler->compileString('{{
            \'Hello world or foo\'
        }}'));

        $this->assertEquals('<?php echo e(myfunc(\'foo or bar\')); ?>',
            $compiler->compileString('{{ myfunc(\'foo or bar\') }}'));
        $this->assertEquals('<?php echo e(myfunc("foo or bar")); ?>',
            $compiler->compileString('{{ myfunc("foo or bar") }}'));
        $this->assertEquals('<?php echo e(myfunc("$name or \'foo\'")); ?>',
            $compiler->compileString('{{ myfunc("$name or \'foo\'") }}'));
    }

    public function testEscapedWithAtEchosAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);

        $this->assertEquals('{{$name}}', $compiler->compileString('@{{$name}}'));
        $this->assertEquals('{{ $name }}', $compiler->compileString('@{{ $name }}'));
        $this->assertEquals('{{
            $name
        }}',
            $compiler->compileString('@{{
            $name
        }}'));
        $this->assertEquals('{{ $name }}
            ',
            $compiler->compileString('@{{ $name }}
            '));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
