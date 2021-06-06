<?php

namespace Illuminate\Tests\View\Blade;

class BladeEchoHandlerTest extends AbstractBladeTestCase
{
    public function testBladeHandlerCanInterceptRegularEchos()
    {
        $this->assertSame(
            '<?php echo e(is_object($exampleObject) ? $__env->stringifyObject($exampleObject) : ($exampleObject)); ?>',
            $this->compiler->compileString('{{$exampleObject}}')
        );
    }

    public function testBladeHandlerCanInterceptRawEchos()
    {
        $this->assertSame(
            '<?php echo is_object($exampleObject) ? $__env->stringifyObject($exampleObject) : ($exampleObject); ?>',
            $this->compiler->compileString('{!!$exampleObject!!}')
        );
    }

    public function testBladeHandlerCanInterceptEscapedEchos()
    {
        $this->assertSame(
            '<?php echo e(is_object($exampleObject) ? $__env->stringifyObject($exampleObject) : ($exampleObject)); ?>',
            $this->compiler->compileString('{{{$exampleObject}}}')
        );
    }

    public function testWhitespaceIsPreservedCorrectly()
    {
        $this->assertSame(
            "<?php echo e(is_object(\$exampleObject) ? \$__env->stringifyObject(\$exampleObject) : (\$exampleObject)); ?>\n\n",
            $this->compiler->compileString("{{\$exampleObject}}\n")
        );
    }
}
