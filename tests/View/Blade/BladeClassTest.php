<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeClassTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testToggledClassesAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo \'class="\'.implode(\' \', array_toggled_values([])).\'"\'; ?>', $compiler->compileString('@class([])'));
        $this->assertEquals('<?php echo \'class="\'.implode(\' \', array_toggled_values(["foo"])).\'"\'; ?>', $compiler->compileString('@class(["foo"])'));
        $this->assertEquals('<?php echo \'class="\'.implode(\' \', array_toggled_values(["foo", "bar" => true, "baz" => false])).\'"\'; ?>', $compiler->compileString('@class(["foo", "bar" => true, "baz" => false])'));
        $this->assertEquals('<div <?php echo \'class="\'.implode(\' \', array_toggled_values(["foo"])).\'"\'; ?>>Hi</div>', $compiler->compileString('<div @class(["foo"])>Hi</div>'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
