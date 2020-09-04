<?php

namespace Illuminate\Tests\View\Blade;

class BladeIfRouteIsTest extends AbstractBladeTestCase
{
    public function testIfRouteIsAreCompiled()
    {
        $string = '@ifElseRouteIs("api", "api-route", "not-api-route")
breeze';
        $expected = '<?php if(request()->routeIs("api")) { echo "api-route"; } else { echo "not-api-route"; } ?>
breeze';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testIfRouteIsNoElseAreCompiled()
    {
        $string = '@ifRouteIs("api", "api-route")
breeze';
        $expected = '<?php if(request()->routeIs("api")) { echo "api-route"; } ?>
breeze';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testRouteAreCompiled()
    {
        $string = '@route("api")
breeze';
        $expected = '<?php echo route("api"); ?>
breeze';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
