<?php

namespace Illuminate\Tests\Blade;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class BladeTryCatchFinallyStatementsTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testTryCatchStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@try
breeze
@catch (\Exception $e)
boom
@endtry';
        $expected = '<?php try { ?>
breeze
<?php } catch (\Exception $e) { ?>
boom
<?php } ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testTryMultipleCatchStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@try
breeze
@catch(\Exception $e)
boom
@catch(\Error $e)
bang
@endtry';
        $expected = '<?php try { ?>
breeze
<?php } catch (\Exception $e) { ?>
boom
<?php } catch (\Error $e) { ?>
bang
<?php } ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testTryCatchFinallyStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@try
breeze
@catch(\Exception $e)
boom
@finally
bang
@endtry';
        $expected = '<?php try { ?>
breeze
<?php } catch (\Exception $e) { ?>
boom
<?php } finally { ?>
bang
<?php } ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    public function testTryMultipleCatchFinallyStatementsAreCompiled()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $string = '@try
breeze
@catch(\Exception $e)
boom
@catch(\Error $e)
bang
@finally
bang
@endtry';
        $expected = '<?php try { ?>
breeze
<?php } catch (\Exception $e) { ?>
boom
<?php } catch (\Error $e) { ?>
bang
<?php } finally { ?>
bang
<?php } ?>';
        $this->assertEquals($expected, $compiler->compileString($string));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }
}
