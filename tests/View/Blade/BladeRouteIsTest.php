<?php

namespace Illuminate\Tests\View\Blade;

class BladeRouteIsTest extends AbstractBladeTestCase
{
    public function testRouteIsCompiled()
    {
        $string = '@routeIs(foo) bar @endRouteIs';
        $expected = '<?php if(request()->routeIs(foo)): ?> bar <?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
