<?php

namespace Illuminate\Tests\View\Blade;

class BladeErrorTest extends AbstractBladeTestCase
{
    public function testErrorsAreCompiled()
    {
        $string = '
@error(\'email\')
    <span>{{ $message }}</span>
@enderror';
        $expected = '
<?php $__args = [\'email\'];
$__bag = $errors->getBag($__args[1] ?? \'default\');
if ($__bag->has($__args[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__args[0]); ?>
    <span><?php echo e($message); ?></span>
<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__args, $__bag); ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testErrorsWithBagsAreCompiled()
    {
        $string = '
@error(\'email\', \'customBag\')
    <span>{{ $message }}</span>
@enderror';
        $expected = '
<?php $__args = [\'email\', \'customBag\'];
$__bag = $errors->getBag($__args[1] ?? \'default\');
if ($__bag->has($__args[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__args[0]); ?>
    <span><?php echo e($message); ?></span>
<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__args, $__bag); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
