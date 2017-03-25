<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeIfUserIsGuestTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testIfUserIsGuestAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@guest
breeze
@endguest';
        $expected = '<?php if(! \Illuminate\Contracts\Auth\Authenticatable::user()): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
