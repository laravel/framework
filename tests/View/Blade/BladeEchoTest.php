<?php

namespace Illuminate\Tests\View\Blade;

class BladeEchoTest extends AbstractBladeTestCase
{
    public function testEchosAreCompiled()
    {
        $this->assertSame('<?php echo is_object($name) ? $__env->stringifyObject($name) : ($name); ?>', $this->compiler->compileString('{!!$name!!}'));
        $this->assertSame('<?php echo is_object($name) ? $__env->stringifyObject($name) : ($name); ?>', $this->compiler->compileString('{!! $name !!}'));
        $this->assertSame('<?php echo is_object($name) ? $__env->stringifyObject($name) : ($name); ?>', $this->compiler->compileString('{!!
            $name
        !!}'));

        $this->assertSame('<?php echo e(is_object($name) ? $__env->stringifyObject($name) : ($name)); ?>', $this->compiler->compileString('{{{$name}}}'));
        $this->assertSame('<?php echo e(is_object($name) ? $__env->stringifyObject($name) : ($name)); ?>', $this->compiler->compileString('{{$name}}'));
        $this->assertSame('<?php echo e(is_object($name) ? $__env->stringifyObject($name) : ($name)); ?>', $this->compiler->compileString('{{ $name }}'));
        $this->assertSame('<?php echo e(is_object($name) ? $__env->stringifyObject($name) : ($name)); ?>', $this->compiler->compileString('{{
            $name
        }}'));
        $this->assertSame("<?php echo e(is_object(\$name) ? \$__env->stringifyObject(\$name) : (\$name)); ?>\n\n", $this->compiler->compileString("{{ \$name }}\n"));
        $this->assertSame("<?php echo e(is_object(\$name) ? \$__env->stringifyObject(\$name) : (\$name)); ?>\r\n\r\n", $this->compiler->compileString("{{ \$name }}\r\n"));
        $this->assertSame("<?php echo e(is_object(\$name) ? \$__env->stringifyObject(\$name) : (\$name)); ?>\n\n", $this->compiler->compileString("{{ \$name }}\n"));
        $this->assertSame("<?php echo e(is_object(\$name) ? \$__env->stringifyObject(\$name) : (\$name)); ?>\r\n\r\n", $this->compiler->compileString("{{ \$name }}\r\n"));

        $this->assertSame('<?php echo e(is_object("Hello world or foo") ? $__env->stringifyObject("Hello world or foo") : ("Hello world or foo")); ?>',
            $this->compiler->compileString('{{ "Hello world or foo" }}'));
        $this->assertSame('<?php echo e(is_object("Hello world or foo") ? $__env->stringifyObject("Hello world or foo") : ("Hello world or foo")); ?>',
            $this->compiler->compileString('{{"Hello world or foo"}}'));
        $this->assertSame('<?php echo e(is_object($foo + $or + $baz) ? $__env->stringifyObject($foo + $or + $baz) : ($foo + $or + $baz)); ?>', $this->compiler->compileString('{{$foo + $or + $baz}}'));
        $this->assertSame('<?php echo e(is_object("Hello world or foo") ? $__env->stringifyObject("Hello world or foo") : ("Hello world or foo")); ?>', $this->compiler->compileString('{{
            "Hello world or foo"
        }}'));

        $this->assertSame('<?php echo e(is_object(\'Hello world or foo\') ? $__env->stringifyObject(\'Hello world or foo\') : (\'Hello world or foo\')); ?>',
            $this->compiler->compileString('{{ \'Hello world or foo\' }}'));
        $this->assertSame('<?php echo e(is_object(\'Hello world or foo\') ? $__env->stringifyObject(\'Hello world or foo\') : (\'Hello world or foo\')); ?>',
            $this->compiler->compileString('{{\'Hello world or foo\'}}'));
        $this->assertSame('<?php echo e(is_object(\'Hello world or foo\') ? $__env->stringifyObject(\'Hello world or foo\') : (\'Hello world or foo\')); ?>', $this->compiler->compileString('{{
            \'Hello world or foo\'
        }}'));

        $this->assertSame('<?php echo e(is_object(myfunc(\'foo or bar\')) ? $__env->stringifyObject(myfunc(\'foo or bar\')) : (myfunc(\'foo or bar\'))); ?>',
            $this->compiler->compileString('{{ myfunc(\'foo or bar\') }}'));
        $this->assertSame('<?php echo e(is_object(myfunc("foo or bar")) ? $__env->stringifyObject(myfunc("foo or bar")) : (myfunc("foo or bar"))); ?>',
            $this->compiler->compileString('{{ myfunc("foo or bar") }}'));
        $this->assertSame('<?php echo e(is_object(myfunc("$name or \'foo\'")) ? $__env->stringifyObject(myfunc("$name or \'foo\'")) : (myfunc("$name or \'foo\'"))); ?>',
            $this->compiler->compileString('{{ myfunc("$name or \'foo\'") }}'));
    }

    public function testEscapedWithAtEchosAreCompiled()
    {
        $this->assertSame('{{$name}}', $this->compiler->compileString('@{{$name}}'));
        $this->assertSame('{{ $name }}', $this->compiler->compileString('@{{ $name }}'));
        $this->assertSame('{{
            $name
        }}',
            $this->compiler->compileString('@{{
            $name
        }}'));
        $this->assertSame('{{ $name }}
            ',
            $this->compiler->compileString('@{{ $name }}
            '));
    }
}
