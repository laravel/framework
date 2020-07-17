<?php

namespace Illuminate\Tests\View\Blade;

class BladeElseAuthStatementsTest extends AbstractBladeTestCase
{
    public function testElseAuthStatementsAreCompiled()
    {
        $string = '@auth("api")
breeze
@elseauth("standard")
wheeze
@endauth';
        $expected = '<?php if(auth()->guard("api")->check()): ?>
breeze
<?php elseif(auth()->guard("standard")->check()): ?>
wheeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }

    public function testPlainElseAuthStatementsAreCompiled()
    {
        $string = '@auth("api")
breeze
@elseauth
wheeze
@endauth';
        $expected = '<?php if(auth()->guard("api")->check()): ?>
breeze
<?php elseif(auth()->guard()->check()): ?>
wheeze
<?php endif; ?>';
        $this->assertEquals($expected, $this->compiler->compileString($string));
    }
}
