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
<?php $__errorArgs = [\'email\'];
$__bag = $errors->getBag($__errorArgs[1] ?? \'default\');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
if (isset($messages)) { $__messagesOriginal = $messages; }
$message = $__bag->first($__errorArgs[0]); ?>
    <span><?php echo e($message); ?></span>
<?php unset($message, $messages);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
if (isset($__messagesOriginal)) { $messages = $__messagesOriginal; }
endif;
unset($__errorArgs, $__bag); ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testErrorsWithBagsAreCompiled()
    {
        $string = '
@error(\'email\', \'customBag\')
    <span>{{ $message }}</span>
@enderror';
        $expected = '
<?php $__errorArgs = [\'email\', \'customBag\'];
$__bag = $errors->getBag($__errorArgs[1] ?? \'default\');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
if (isset($messages)) { $__messagesOriginal = $messages; }
$message = $__bag->first($__errorArgs[0]); ?>
    <span><?php echo e($message); ?></span>
<?php unset($message, $messages);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
if (isset($__messagesOriginal)) { $messages = $__messagesOriginal; }
endif;
unset($__errorArgs, $__bag); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testErrorsArraysAreCompiled()
    {
        $string = '
@error([\'email\', \'phone\'])
    <span>{{ $message }}</span>
@enderror';
        $expected = '
<?php $__errorArgs = [[\'email\', \'phone\']];
$__bag = $errors->getBag($__errorArgs[1] ?? \'default\');
if ($__bag->hasAny($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
if (isset($messages)) { $__messagesOriginal = $messages; }
$messages = array_reduce($__errorArgs[0], function($carry, $__error) use($__bag) {
    $newline = $__bag->first($__error);
    if($newline) $carry[] = $newline;
    return $carry;
}, []);
$message = implode('.', $messages); ?>
    <span><?php echo e($message); ?></span>
<?php unset($message, $messages);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
if (isset($__messagesOriginal)) { $messages = $__messagesOriginal; }
endif;
unset($__errorArgs, $__bag); ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
