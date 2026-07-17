<?php

namespace Illuminate\Tests\View\Blade;

class BladeIfGuestStatementsTest extends AbstractBladeTestCase
{
    public function testIfStatementsAreCompiled()
    {
        $string = '@guest("api")
breeze
@endguest';
        $expected = '<?php if(auth()->guard("api")->guest()): ?>
breeze
<?php endif; ?>';
        $this->assertSame($expected, $this->compiler->compileString($string));
    }
}
