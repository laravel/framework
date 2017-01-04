<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeCustomTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testCustomPhpCodeIsCorrectlyHandled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php if($test): ?> <?php @show(\'test\'); ?> <?php endif; ?>', $compiler->compileString("@if(\$test) <?php @show('test'); ?> @endif"));
    }

    public function testMixingYieldAndEcho()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals('<?php echo $__env->yieldContent(\'title\'); ?> - <?php echo e(Config::get(\'site.title\')); ?>', $compiler->compileString("@yield('title') - {{Config::get('site.title')}}"));
    }

    public function testCustomExtensionsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $compiler->extend(function ($value) {
            return str_replace('foo', 'bar', $value);
        });
        $this->assertEquals('bar', $compiler->compileString('foo'));
    }

    public function testCustomStatements()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertCount(0, $compiler->getCustomDirectives());
        $compiler->directive('customControl', function ($expression) {
            return "<?php echo custom_control({$expression}); ?>";
        });
        $this->assertCount(1, $compiler->getCustomDirectives());

        $string = '@if($foo)
@customControl(10, $foo, \'bar\')
@endif';
        $expected = '<?php if($foo): ?>
<?php echo custom_control(10, $foo, \'bar\'); ?>
<?php endif; ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testCustomShortStatements()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $compiler->directive('customControl', function ($expression) {
            return '<?php echo custom_control(); ?>';
        });

        $string = '@customControl';
        $expected = '<?php echo custom_control(); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testCustomExtensionOverwritesCore()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $compiler->directive('foreach', function ($expression) {
            return '<?php custom(); ?>';
        });

        $string = '@foreach';
        $expected = '<?php custom(); ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
