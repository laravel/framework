<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeHasAnySectionsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testHasAnySectionsStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@hasAnySections(["section1", "section2"])
breeze
@endif';
        $expected = '<?php if (! empty(trim(implode(\'\', array_map(function($section) use ($__env) { return $__env->yieldContent($section); }, (["section1", "section2"])))))): ?>
breeze
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
