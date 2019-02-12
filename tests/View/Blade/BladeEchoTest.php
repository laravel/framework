<?php

namespace Illuminate\Tests\View\Blade;

class BladeEchoTest extends AbstractBladeTestCase
{
    public function testEchosAreCompiled()
    {
        $this->assertEquals('<?php echo $name; ?>', $this->compiler->compileString('{!!$name!!}'));
        $this->assertEquals('<?php echo $name; ?>', $this->compiler->compileString('{!! $name !!}'));
        $this->assertEquals('<?php echo $name; ?>', $this->compiler->compileString('{!!
            $name
        !!}'));

        $this->assertEquals('<?php echo e($name); ?>', $this->compiler->compileString('{{{$name}}}'));
        $this->assertEquals('<?php echo e($name); ?>', $this->compiler->compileString('{{$name}}'));
        $this->assertEquals('<?php echo e($name); ?>', $this->compiler->compileString('{{ $name }}'));
        $this->assertEquals('<?php echo e($name); ?>', $this->compiler->compileString('{{
            $name
        }}'));
        $this->assertEquals("<?php echo e(\$name); ?>\n\n", $this->compiler->compileString("{{ \$name }}\n"));
        $this->assertEquals("<?php echo e(\$name); ?>\r\n\r\n", $this->compiler->compileString("{{ \$name }}\r\n"));
        $this->assertEquals("<?php echo e(\$name); ?>\n\n", $this->compiler->compileString("{{ \$name }}\n"));
        $this->assertEquals("<?php echo e(\$name); ?>\r\n\r\n", $this->compiler->compileString("{{ \$name }}\r\n"));

        $this->assertEquals('<?php echo e("Hello world or foo"); ?>',
            $this->compiler->compileString('{{ "Hello world or foo" }}'));
        $this->assertEquals('<?php echo e("Hello world or foo"); ?>',
            $this->compiler->compileString('{{"Hello world or foo"}}'));
        $this->assertEquals('<?php echo e($foo + $or + $baz); ?>', $this->compiler->compileString('{{$foo + $or + $baz}}'));
        $this->assertEquals('<?php echo e("Hello world or foo"); ?>', $this->compiler->compileString('{{
            "Hello world or foo"
        }}'));

        $this->assertEquals('<?php echo e(\'Hello world or foo\'); ?>',
            $this->compiler->compileString('{{ \'Hello world or foo\' }}'));
        $this->assertEquals('<?php echo e(\'Hello world or foo\'); ?>',
            $this->compiler->compileString('{{\'Hello world or foo\'}}'));
        $this->assertEquals('<?php echo e(\'Hello world or foo\'); ?>', $this->compiler->compileString('{{
            \'Hello world or foo\'
        }}'));

        $this->assertEquals('<?php echo e(myfunc(\'foo or bar\')); ?>',
            $this->compiler->compileString('{{ myfunc(\'foo or bar\') }}'));
        $this->assertEquals('<?php echo e(myfunc("foo or bar")); ?>',
            $this->compiler->compileString('{{ myfunc("foo or bar") }}'));
        $this->assertEquals('<?php echo e(myfunc("$name or \'foo\'")); ?>',
            $this->compiler->compileString('{{ myfunc("$name or \'foo\'") }}'));
    }

    public function testEscapedWithAtEchosAreCompiled()
    {
        $this->assertEquals('{{$name}}', $this->compiler->compileString('@{{$name}}'));
        $this->assertEquals('{{ $name }}', $this->compiler->compileString('@{{ $name }}'));
        $this->assertEquals('{{
            $name
        }}',
            $this->compiler->compileString('@{{
            $name
        }}'));
        $this->assertEquals('{{ $name }}
            ',
            $this->compiler->compileString('@{{ $name }}
            '));
    }
}
