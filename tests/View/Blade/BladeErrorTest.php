<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

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
if (isset($message)) { $__messagePrevious[] = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
    <span><?php echo e($message); ?></span>
<?php unset($message);
if (isset($__messagePrevious) && !empty($__messagePrevious)) { $message = array_pop($__messagePrevious); }
if (isset($__messagePrevious) && empty($__messagePrevious)) { unset($__messagePrevious); }
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
if (isset($message)) { $__messagePrevious[] = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
    <span><?php echo e($message); ?></span>
<?php unset($message);
if (isset($__messagePrevious) && !empty($__messagePrevious)) { $message = array_pop($__messagePrevious); }
if (isset($__messagePrevious) && empty($__messagePrevious)) { unset($__messagePrevious); }
endif;
unset($__errorArgs, $__bag); ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testNestedErrorsRestoreTheOriginalMessageVariable()
    {
        $compiled = $this->compiler->compileString(
            '@error(\'email\'){{ $message }}|@error(\'password\'){{ $message }}|@enderror{{ $message }}|@enderror{{ $message }}'
        );

        $errors = (new ViewErrorBag)->put('default', new MessageBag([
            'email' => 'Bad email',
            'password' => 'Bad password',
        ]));

        $render = function ($compiled, $errors, $message) {
            ob_start();
            eval('?>'.$compiled);

            return ob_get_clean();
        };

        $this->assertSame('Bad email|Bad password|Bad email|original', $render($compiled, $errors, 'original'));
    }
}
