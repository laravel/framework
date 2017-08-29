<?php

namespace Illuminate\Tests\View\Blade;

class BladeDuskTest extends AbstractBladeTestCase
{
    public function testDuskIsCompiled()
    {
        $this->assertEquals('', $this->compiler->compileString("@dusk('foo')"));
    }
}
