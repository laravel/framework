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
$__errorArgs[0] = is_array($__errorArgs[0]) ? $__errorArgs[0] : [$__errorArgs[0]];
if ($__bag->hasAny($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = "";
$__i = 0;
while ($message === "") {
     $message = $__bag->first($__errorArgs[0][$__i++]);
} ?>
    <span><?php echo e($message); ?></span>
<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag, $__i); ?>';

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
$__errorArgs[0] = is_array($__errorArgs[0]) ? $__errorArgs[0] : [$__errorArgs[0]];
if ($__bag->hasAny($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = "";
$__i = 0;
while ($message === "") {
     $message = $__bag->first($__errorArgs[0][$__i++]);
} ?>
    <span><?php echo e($message); ?></span>
<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testErrorsWithMultipleKeysAreCompiled()
    {
        $string = '
@error([\'email\', \'name\'])
    <span>{{ $message }}</span>
@enderror';
        $expected = '
<?php $__errorArgs = [[\'email\', \'name\']];
$__bag = $errors->getBag($__errorArgs[1] ?? \'default\');
$__errorArgs[0] = is_array($__errorArgs[0]) ? $__errorArgs[0] : [$__errorArgs[0]];
if ($__bag->hasAny($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = "";
$__i = 0;
while ($message === "") {
     $message = $__bag->first($__errorArgs[0][$__i++]);
} ?>
    <span><?php echo e($message); ?></span>
<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag, $__i); ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testErrorsWithMultipleKeysWithBagsAreCompiled()
    {
        $string = '
@error([\'email\', \'name\'], \'customBag\')
    <span>{{ $message }}</span>
@enderror';
        $expected = '
<?php $__errorArgs = [[\'email\', \'name\'], \'customBag\'];
$__bag = $errors->getBag($__errorArgs[1] ?? \'default\');
$__errorArgs[0] = is_array($__errorArgs[0]) ? $__errorArgs[0] : [$__errorArgs[0]];
if ($__bag->hasAny($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = "";
$__i = 0;
while ($message === "") {
     $message = $__bag->first($__errorArgs[0][$__i++]);
} ?>
    <span><?php echo e($message); ?></span>
<?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag, $__i); ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
