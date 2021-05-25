<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Support\Fluent;

class BladeEchoHandlerTest extends AbstractBladeTestCase
{
    public function testBladeHandlersCanBeAddedForAGivenClass()
    {
        $this->compiler->handle(Fluent::class, function($object) {
            return "Hello World";
        });

        $this->assertSame('Hello World', $this->compiler::$echoHandlers[Fluent::class](new Fluent()));
    }

    public function testBladeHandlerCanInterceptEscapedEchos()
    {
        $echoHandlerArray = get_class($this->compiler) . "::\$echoHandlers";

        $this->assertSame(
            "<?php echo e(is_object(\$exampleObject) && isset({$echoHandlerArray}[get_class(\$exampleObject)])
            ? call_user_func_array({$echoHandlerArray}[get_class(\$exampleObject)], [\$exampleObject])
            : \$exampleObject); ?>",
            $this->compiler->compileString('{{$exampleObject}}')
        );
    }

    public function testBladeHandlerCanInterceptRawEchos()
    {
        $echoHandlerArray = get_class($this->compiler) . "::\$echoHandlers";

        $this->assertSame(
            "<?php echo is_object(\$exampleObject) && isset({$echoHandlerArray}[get_class(\$exampleObject)])
            ? call_user_func_array({$echoHandlerArray}[get_class(\$exampleObject)], [\$exampleObject])
            : \$exampleObject; ?>",
            $this->compiler->compileString('{!!$exampleObject!!}')
        );
    }
}
