<?php

namespace Illuminate\Tests\View\Blade;

class BladeEscapedTest extends AbstractBladeTestCase
{
    public function testEscapedWithAtDirectivesAreCompiled()
    {
        $this->assertSame('@foreach', $this->compiler->compileString('@@foreach'));
        $this->assertSame('@verbatim @continue @endverbatim', $this->compiler->compileString('@@verbatim @@continue @@endverbatim'));
        $this->assertSame('@foreach($i as $x)', $this->compiler->compileString('@@foreach($i as $x)'));
        $this->assertSame('@continue @break', $this->compiler->compileString('@@continue @@break'));
        $this->assertSame('@foreach(
            $i as $x
        )', $this->compiler->compileString('@@foreach(
            $i as $x
        )'));
    }
}
