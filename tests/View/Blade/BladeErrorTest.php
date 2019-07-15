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
<?php if ($errors->has(\'email\')) :
if (isset($message)) { $messageCache = $message; }
$message = $errors->first(\'email\'); ?>
    <span><?php echo e($message); ?></span>
<?php unset($message);
if (isset($messageCache)) { $message = $messageCache; }
endif; ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
