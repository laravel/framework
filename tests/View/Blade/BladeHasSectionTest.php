<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeHasSectionTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testHasSectionStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@hasSection("section")
breeze
@endif';
        $expected = '<?php if (! empty(trim($__env->yieldContent("section")))): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
