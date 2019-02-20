<?php

namespace Illuminate\Tests\View;

use Mockery as m;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

class ViewBladeCompilerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testIsExpiredReturnsTrueIfCompiledFileDoesntExist()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.sha1('foo').'.php')->andReturn(false);
        $this->assertTrue($compiler->isExpired('foo'));
    }

    public function testCannotConstructWithBadCachePath()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide a valid cache path.');

        new BladeCompiler($this->getFiles(), null);
    }

    public function testIsExpiredReturnsTrueWhenModificationTimesWarrant()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.sha1('foo').'.php')->andReturn(true);
        $files->shouldReceive('lastModified')->once()->with('foo')->andReturn(100);
        $files->shouldReceive('lastModified')->once()->with(__DIR__.'/'.sha1('foo').'.php')->andReturn(0);
        $this->assertTrue($compiler->isExpired('foo'));
    }

    public function testCompilePathIsProperlyCreated()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals(__DIR__.'/'.sha1('foo').'.php', $compiler->getCompiledPath('foo'));
    }

    public function testCompileCompilesFileAndReturnsContents()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.sha1('foo').'.php', "<?php /* foo */ ?>\nHello World");
        $compiler->compile('foo');
    }

    public function testCompileCompilesAndGetThePath()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.sha1('foo').'.php', "<?php /* foo */ ?>\nHello World");
        $compiler->compile('foo');
        $this->assertEquals('foo', $compiler->getPath());
    }

    public function testCompileSetAndGetThePath()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $compiler->setPath('foo');
        $this->assertEquals('foo', $compiler->getPath());
    }

    public function testCompileWithPathSetBefore()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.sha1('foo').'.php', "<?php /* foo */ ?>\nHello World");
        // set path before compilation
        $compiler->setPath('foo');
        // trigger compilation with null $path
        $compiler->compile();
        $this->assertEquals('foo', $compiler->getPath());
    }

    public function testRawTagsCanBeSetToLegacyValues()
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $compiler->setEchoFormat('%s');

        $this->assertEquals('<?php echo e($name); ?>', $compiler->compileString('{{{ $name }}}'));
        $this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{{ $name }}'));
        $this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{{
            $name
        }}'));
    }

    public function testIncludePathToTemplate()
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.sha1('foo').'.php', "<?php /* foo */ ?>\nHello World");
        $compiler->compile('foo');
    }

    protected function getFiles()
    {
        return m::mock(Filesystem::class);
    }
}
