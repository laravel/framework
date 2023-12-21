<?php

namespace Illuminate\Tests\View\Blade;

class BladeSessionTest extends AbstractBladeTestCase
{
    public function testSessionsAreCompiled()
    {
        $string = '
@session(\'status\')
    <span>{{ $value }}</span>
@endsession';
        $expected = '
<?php $__sessionArgs = [\'status\'];
if (session()->has($__sessionArgs[0])) :
if (isset($value)) { $__sessionPrevious[] = $value; }
$value = session()->get($__sessionArgs[0]); ?>
    <span><?php echo e($value); ?></span>
<?php unset($value);
if (isset($__sessionPrevious) && !empty($__sessionPrevious)) { $value = array_pop($__sessionPrevious); }
if (isset($__sessionPrevious) && empty($__sessionPrevious)) { unset($__sessionPrevious); }
endif;
unset($__sessionArgs); ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
