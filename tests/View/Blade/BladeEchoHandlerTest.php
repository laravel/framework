<?php

namespace Illuminate\Tests\View\Blade;

use Exception;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

class BladeEchoHandlerTest extends AbstractBladeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler->stringable(function (Fluent $object) {
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

    public function testHandlerLogicWorksCorrectly()
    {
        $this->expectExceptionMessage('The fluent object has been successfully handled!');

        $this->compiler->stringable(Fluent::class, function ($object) {
            throw new Exception('The fluent object has been successfully handled!');
        });

        app()->singleton('blade.compiler', function () {
            return $this->compiler;
        });

        $exampleObject = new Fluent();

        eval(
            Str::of($this->compiler->compileString('{{$exampleObject}}'))
            ->after('<?php')
            ->beforeLast('?>')
        );
    }
}
