<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeIfUserIsLoggedTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testIfUserIsLoggedAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@loggedin
breeze
@endloggedin';
        $expected = '<?php if(\Illuminate\Contracts\Auth\Authenticatable::user()): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
