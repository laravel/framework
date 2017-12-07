<?php

namespace Illuminate\Tests\View\Blade;

class BladeEscapedTest extends AbstractBladeTestCase
{
    public function testEscapedWithAtDirectivesAreCompiled()
    {
        $this->assertEquals('@foreach', $this->compiler->compileString('@@foreach'));
        $this->assertEquals('@verbatim @continue @endverbatim', $this->compiler->compileString('@@verbatim @@continue @@endverbatim'));
        $this->assertEquals('@foreach($i as $x)', $this->compiler->compileString('@@foreach($i as $x)'));
        $this->assertEquals('@continue @break', $this->compiler->compileString('@@continue @@break'));
        $this->assertEquals('@foreach(
            $i as $x
        )', $this->compiler->compileString('@@foreach(
            $i as $x
        )'));
    }
}
