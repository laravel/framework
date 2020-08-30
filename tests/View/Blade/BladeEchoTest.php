<?php

namespace Illuminate\Tests\View\Blade;

class BladeEchoTest extends AbstractBladeTestCase
{
    public function testEchosAreCompiled()
    {
        $this->assertSame('<?php echo $name; ?>', $this->compiler->compileString('{!!$name!!}'));
        $this->assertSame('<?php echo $name; ?>', $this->compiler->compileString('{!! $name !!}'));
        $this->assertSame('<?php echo $name; ?>', $this->compiler->compileString('{!!
            $name
        !!}'));

        $this->assertSame('<?php echo e($name); ?>', $this->compiler->compileString('{{{$name}}}'));
        $this->assertSame('<?php echo e($name); ?>', $this->compiler->compileString('{{$name}}'));
        $this->assertSame('<?php echo e($name); ?>', $this->compiler->compileString('{{ $name }}'));
        $this->assertSame('<?php echo e($name); ?>', $this->compiler->compileString('{{
            $name
        }}'));
        $this->assertSame("<?php echo e(\$name); ?>\n\n", $this->compiler->compileString("{{ \$name }}\n"));
        $this->assertSame("<?php echo e(\$name); ?>\r\n\r\n", $this->compiler->compileString("{{ \$name }}\r\n"));
        $this->assertSame("<?php echo e(\$name); ?>\n\n", $this->compiler->compileString("{{ \$name }}\n"));
        $this->assertSame("<?php echo e(\$name); ?>\r\n\r\n", $this->compiler->compileString("{{ \$name }}\r\n"));

        $this->assertSame('<?php echo e("Hello world or foo"); ?>',
            $this->compiler->compileString('{{ "Hello world or foo" }}'));
        $this->assertSame('<?php echo e("Hello world or foo"); ?>',
            $this->compiler->compileString('{{"Hello world or foo"}}'));
        $this->assertSame('<?php echo e($foo + $or + $baz); ?>', $this->compiler->compileString('{{$foo + $or + $baz}}'));
        $this->assertSame('<?php echo e("Hello world or foo"); ?>', $this->compiler->compileString('{{
            "Hello world or foo"
        }}'));

        $this->assertSame('<?php echo e(\'Hello world or foo\'); ?>',
            $this->compiler->compileString('{{ \'Hello world or foo\' }}'));
        $this->assertSame('<?php echo e(\'Hello world or foo\'); ?>',
            $this->compiler->compileString('{{\'Hello world or foo\'}}'));
        $this->assertSame('<?php echo e(\'Hello world or foo\'); ?>', $this->compiler->compileString('{{
            \'Hello world or foo\'
        }}'));

        $this->assertSame('<?php echo e(myfunc(\'foo or bar\')); ?>',
            $this->compiler->compileString('{{ myfunc(\'foo or bar\') }}'));
        $this->assertSame('<?php echo e(myfunc("foo or bar")); ?>',
            $this->compiler->compileString('{{ myfunc("foo or bar") }}'));
        $this->assertSame('<?php echo e(myfunc("$name or \'foo\'")); ?>',
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
