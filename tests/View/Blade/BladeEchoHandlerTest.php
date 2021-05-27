<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Support\Fluent;

class BladeEchoHandlerTest extends AbstractBladeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler->handle(Fluent::class, function ($object) {
            return 'Hello World';
        });
    }

    public function testBladeHandlersCanBeAddedForAGivenClass()
    {
        $this->assertSame('Hello World', $this->compiler->echoHandlers[Fluent::class](new Fluent()));
    }

    public function testBladeHandlerCanInterceptRegularEchos()
    {
        $this->assertSame(
            "<?php echo e(is_object(\$exampleObject) && isset(app('blade.compiler')->echoHandlers[get_class(\$exampleObject)]) ? call_user_func_array(app('blade.compiler')->echoHandlers[get_class(\$exampleObject)], [\$exampleObject]) : \$exampleObject); ?>",
            $this->compiler->compileString('{{$exampleObject}}')
        );
    }

    public function testBladeHandlerCanInterceptRawEchos()
    {
        $this->assertSame(
            "<?php echo is_object(\$exampleObject) && isset(app('blade.compiler')->echoHandlers[get_class(\$exampleObject)]) ? call_user_func_array(app('blade.compiler')->echoHandlers[get_class(\$exampleObject)], [\$exampleObject]) : \$exampleObject; ?>",
            $this->compiler->compileString('{!!$exampleObject!!}')
        );
    }

    public function testBladeHandlerCanInterceptEscapedEchos()
    {
        $this->assertSame(
            "<?php echo e(is_object(\$exampleObject) && isset(app('blade.compiler')->echoHandlers[get_class(\$exampleObject)]) ? call_user_func_array(app('blade.compiler')->echoHandlers[get_class(\$exampleObject)], [\$exampleObject]) : \$exampleObject); ?>",
            $this->compiler->compileString('{{{$exampleObject}}}')
        );
    }

    public function testWhitespaceIsPreservedCorrectly()
    {
        $this->assertSame(
            "<?php echo e(is_object(\$exampleObject) && isset(app('blade.compiler')->echoHandlers[get_class(\$exampleObject)]) ? call_user_func_array(app('blade.compiler')->echoHandlers[get_class(\$exampleObject)], [\$exampleObject]) : \$exampleObject); ?>\n\n",
            $this->compiler->compileString("{{\$exampleObject}}\n")
        );
    }
}
