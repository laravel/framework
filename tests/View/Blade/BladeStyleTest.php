<?php

namespace Illuminate\Tests\View\Blade;

class BladeStyleTest extends AbstractBladeTestCase
{
    public function testStylesAreConditionallyCompiledFromArray()
    {
        $string = "<span @style(['font-weight: bold', 'text-decoration: underline', 'color: red' => true, 'margin-top: 10px' => false])></span>";
        $expected = "<span <?php \$__styles = \Illuminate\Support\Arr::toCssStyles(['font-weight: bold', 'text-decoration: underline', 'color: red' => true, 'margin-top: 10px' => false]); echo \$__styles !== '' ? 'style=\"'.\$__styles.'\"' : ''; ?>></span>";

        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testStyleAttributeIsOmittedWhenArrayIsEmpty()
    {
        $template = $this->compiler->compileString('<span @style([])></span>');

        ob_start();
        eval('?>'.$template);
        $output = ob_get_clean();

        $this->assertSame('<span ></span>', $output);
    }

    public function testStyleAttributeIsOmittedWhenAllConditionsAreFalse()
    {
        $template = $this->compiler->compileString("<span @style(['color: red' => false, 'margin: 0' => false])></span>");

        ob_start();
        eval('?>'.$template);
        $output = ob_get_clean();

        $this->assertSame('<span ></span>', $output);
    }

    public function testStyleAttributeIsRenderedWhenSomeConditionsAreTrue()
    {
        $template = $this->compiler->compileString("<span @style(['font-weight: bold', 'color: red' => true, 'margin: 0' => false])></span>");

        ob_start();
        eval('?>'.$template);
        $output = ob_get_clean();

        $this->assertSame('<span style="font-weight: bold; color: red;"></span>', $output);
    }

    public function testStyleAttributeIsRenderedWithUnconditionalStyleWhenAllConditionsAreFalse()
    {
        $template = $this->compiler->compileString("<span @style(['font-weight: bold', 'color: red' => false])></span>");

        ob_start();
        eval('?>'.$template);
        $output = ob_get_clean();

        $this->assertSame('<span style="font-weight: bold;"></span>', $output);
    }
}
