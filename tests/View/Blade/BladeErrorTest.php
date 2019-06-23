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
<?php if ($errors->getBag(\'default\')->has(\'email\')) :
if (isset($message)) { $messageCache = $message; }
$message = $errors->getBag(\'default\')->first(\'email\'); ?>
    <span><?php echo e($message); ?></span>
<?php unset($message);
if (isset($messageCache)) { $message = $messageCache; }
endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testErrorsWithCustomBagAreCompiled()
    {
        $string = '
@error(\'email\', \'login\')
    <span>{{ $message }}</span>
@enderror';
        $expected = '
<?php if ($errors->getBag(\'login\')->has(\'email\')) :
if (isset($message)) { $messageCache = $message; }
$message = $errors->getBag(\'login\')->first(\'email\'); ?>
    <span><?php echo e($message); ?></span>
<?php unset($message);
if (isset($messageCache)) { $message = $messageCache; }
endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
