<?php

namespace Illuminate\Tests\View\Blade;

class BladeClassTest extends AbstractBladeTestCase
{
    public function testClassesAreConditionallyCompiledFromArray()
    {
        $string = "<span @class(['font-bold', 'mt-4', 'ml-2' => true, 'mr-2' => false])></span>";
        $expected = "<span <?php \$__classes = \Illuminate\Support\Arr::toCssClasses(['font-bold', 'mt-4', 'ml-2' => true, 'mr-2' => false]); echo \$__classes !== '' ? 'class=\"'.\$__classes.'\"' : ''; ?>></span>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testClassAttributeIsOmittedWhenArrayIsEmpty()
    {
        $template = $this->compiler->compileString('<span @class([])></span>');

        ob_start();
        eval('?>'.$template);
        $output = ob_get_clean();

        $this->assertSame('<span ></span>', $output);
    }

    public function testClassAttributeIsOmittedWhenAllConditionsAreFalse()
    {
        $template = $this->compiler->compileString("<span @class(['foo' => false, 'bar' => false])></span>");

        ob_start();
        eval('?>'.$template);
        $output = ob_get_clean();

        $this->assertSame('<span ></span>', $output);
    }

    public function testClassAttributeIsRenderedWhenSomeConditionsAreTrue()
    {
        $template = $this->compiler->compileString("<span @class(['base', 'active' => true, 'hidden' => false])></span>");

        ob_start();
        eval('?>'.$template);
        $output = ob_get_clean();

        $this->assertSame('<span class="base active"></span>', $output);
    }

    public function testClassAttributeIsRenderedWithUnconditionalClassWhenAllConditionsAreFalse()
    {
        $template = $this->compiler->compileString("<span @class(['base', 'active' => false])></span>");

        ob_start();
        eval('?>'.$template);
        $output = ob_get_clean();

        $this->assertSame('<span class="base"></span>', $output);
    }
}
