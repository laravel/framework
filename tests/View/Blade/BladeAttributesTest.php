<?php

namespace Illuminate\Tests\View\Blade;

class BladeAttributesTest extends AbstractBladeTestCase
{
    public function testAttributesAreConditionallyCompiledFromArray()
    {
        $string = "<span @attributes(['class=\"mt-1\"' => true, 'disabled=\"disabled\"' => false, 'role=\"button\"'])></span>";
        $expected = "<span <?php echo \Illuminate\Support\Arr::toHtmlAttributes(['class=\"mt-1\"' => true, 'disabled=\"disabled\"' => false, 'role=\"button\"']); ?>></span>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
