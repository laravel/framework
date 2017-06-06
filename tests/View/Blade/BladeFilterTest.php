<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeFilterTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testAddFilter()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertCount(0, $compiler->getFilters());

        $compiler->filter('arbitrary', function ($expression) {
            return "arbitrary({$expression})";
        });

        $this->assertCount(1, $compiler->getFilters());
        $this->assertArrayHasKey('arbitrary', $compiler->getFilters());
    }

    public function testCompileFilter()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $compiler->filter('upperCase', function ($expression) {
            return "strtoupper({$expression})";
        });

        $this->assertEquals('<?php echo e(strtoupper($foo)); ?>', $compiler->compileString('{{ $foo | upperCase }}'));
    }

    public function testCompileNestedFilters()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $compiler->filter('upperCase', function ($expression) {
            return "strtoupper({$expression})";
        });
        $compiler->filter('trim', function ($expression) {
            return "trim({$expression})";
        });

        $this->assertEquals(
            '<?php echo e(trim(strtoupper($foo))); ?>',
            $compiler->compileString('{{ $foo | upperCase | trim }}')
        );
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Filter [non-existing-filter-name] does not exist.
     */
    public function testTryToCompileNotExistingFilter()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);

        $compiler->compileString('{{ $foo | non-existing-filter-name }}');
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
