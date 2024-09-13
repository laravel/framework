<?php

namespace Illuminate\Tests\View\Blade;

class BladeAttributesTest extends AbstractBladeTestCase
{
    public function testClassesAreConditionallyCompiledFromArray()
    {
        $string = "<span @attributes(['class=\"mt-1\"' => true, 'disabled=\"disabled\"' => false, 'role=\"button\"' ])></span>";
        $expected = "<span class=\"<?php echo \Illuminate\Support\Arr::toCssClasses(['font-bold', 'mt-4', 'ml-2' => true, 'mr-2' => false]); ?>\"></span>";
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
