<?php

namespace Illuminate\Tests\View\Blade;

class BladeContextTest extends AbstractBladeTestCase
{
    public function testContextsAreCompiled()
    {
        $string = '
@context(\'foo\')
    <span>{{ $value }}</span>
@endcontext';
        $expected = '
<?php $__contextArgs = [\'foo\'];
if (context()->has($__contextArgs[0])) :
if (isset($value)) { $__contextPrevious[] = $value; }
$value = context()->get($__contextArgs[0]); ?>
    <span><?php echo e($value); ?></span>
<?php unset($value);
if (isset($__contextPrevious) && !empty($__contextPrevious)) { $value = array_pop($__contextPrevious); }
if (isset($__contextPrevious) && empty($__contextPrevious)) { unset($__contextPrevious); }
endif;
unset($__contextArgs); ?>';

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
