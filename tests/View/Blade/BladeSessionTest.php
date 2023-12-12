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
if (isset($session)) { $__sessionOriginal = $session; }
$session = session()->get($__errorArgs[0]); ?>
    <span><?php echo e($session); ?></span>
<?php unset($session);
if (isset($__sessionOriginal)) { $session = $__sessionOriginal; }
endif;
unset($__sessionArgs); ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
