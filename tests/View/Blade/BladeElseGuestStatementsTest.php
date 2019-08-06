<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\View\Compilers\BladeCompiler;

class BladeElseGuestStatementsTest extends AbstractBladeTestCase
{
    public function testIfStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@guest("api")
breeze
@elseguest("standard")
wheeze
@endguest';
        $expected = '<?php if(auth()->guard("api")->guest()): ?>
breeze
<?php elseif(auth()->guard("standard")->guest()): ?>
wheeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }
}
