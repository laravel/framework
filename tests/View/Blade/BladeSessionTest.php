<?php

namespace Illuminate\Tests\View\Blade;

class BladeSessionTest extends AbstractBladeTestCase
{
    public function testSessionIsCompiled()
    {
        $string = '
@session(\'success\')
    <span>{{ $message }}</span>
@endsession';
        $expected = '
<?php if (session()->has(\'success\')) :
if (isset($message)) { $__messageOriginal = $message; }
$message = session()->get(\'success\'); ?>
    <span><?php echo e($message); ?></span>
<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
