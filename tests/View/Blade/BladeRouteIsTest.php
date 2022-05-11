<?php

namespace Illuminate\Tests\View\Blade;

class BladeRouteIsTest extends AbstractBladeTestCase
{
    public function testRouteIsCompiled()
    {
        $string = '@routeIs(\'foo\', \'bar\') baz @endrouteIs';
        $expected = '<?php if (request()->routeIs(\'foo\', \'bar\')): ?> baz <?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
