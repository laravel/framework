<?php

namespace Illuminate\Tests\View\Blade;

class BladeSessionTest extends AbstractBladeTestCase
{
    public function testSessionsAreCompiled()
    {
        $string = '
@session(\'status\')
    <span>{{ $session }}</span>
@endsession';
        $expected = '
<?php $__sessionArgs = [\'status\'];
if (session()->has($__sessionArgs[0])) :
if (isset($session)) { $__sessionPrevious[] = $session; }
$session = session()->get($__sessionArgs[0]); ?>
    <span><?php echo e($session); ?></span>
<?php unset($session);
if (isset($__sessionPrevious) && !empty($__sessionPrevious)) { $session = array_pop($__sessionPrevious); }
if (isset($__sessionPrevious) && empty($__sessionPrevious)) { unset($__sessionPrevious); }
endif;
unset($__sessionArgs); ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
