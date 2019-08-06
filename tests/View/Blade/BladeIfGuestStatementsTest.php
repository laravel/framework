<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\View\Compilers\BladeCompiler;

class BladeIfGuestStatementsTest extends AbstractBladeTestCase
{
    public function testIfStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@guest("api")
breeze
@endguest';
        $expected = '<?php if(auth()->guard("api")->guest()): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }
}
