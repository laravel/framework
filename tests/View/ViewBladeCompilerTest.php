<?php

namespace Illuminate\Tests\View;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\View\Compilers\BladeCompiler;

class ViewBladeCompilerTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testIsExpiredReturnsTrueIfCompiledFileDoesntExist(): void
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.sha1('foo').'.php')->andReturn(false);
        $this->assertTrue($compiler->isExpired('foo'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Please provide a valid cache path.
     */
    public function testCannotConstructWithBadCachePath(): void
    {
        new BladeCompiler($this->getFiles(), null);
    }

    public function testIsExpiredReturnsTrueWhenModificationTimesWarrant(): void
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('exists')->once()->with(__DIR__.'/'.sha1('foo').'.php')->andReturn(true);
        $files->shouldReceive('lastModified')->once()->with('foo')->andReturn(100);
        $files->shouldReceive('lastModified')->once()->with(__DIR__.'/'.sha1('foo').'.php')->andReturn(0);
        $this->assertTrue($compiler->isExpired('foo'));
    }

    public function testCompilePathIsProperlyCreated(): void
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $this->assertEquals(__DIR__.'/'.sha1('foo').'.php', $compiler->getCompiledPath('foo'));
    }

    public function testCompileCompilesFileAndReturnsContents(): void
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.sha1('foo').'.php', 'Hello World');
        $compiler->compile('foo');
    }

    public function testCompileCompilesAndGetThePath(): void
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.sha1('foo').'.php', 'Hello World');
        $compiler->compile('foo');
        $this->assertEquals('foo', $compiler->getPath());
    }

    public function testCompileSetAndGetThePath(): void
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $compiler->setPath('foo');
        $this->assertEquals('foo', $compiler->getPath());
    }

    public function testCompileWithPathSetBefore(): void
    {
        $compiler = new BladeCompiler($files = $this->getFiles(), __DIR__);
        $files->shouldReceive('get')->once()->with('foo')->andReturn('Hello World');
        $files->shouldReceive('put')->once()->with(__DIR__.'/'.sha1('foo').'.php', 'Hello World');
        // set path before compilation
        $compiler->setPath('foo');
        // trigger compilation with null $path
        $compiler->compile();
        $this->assertEquals('foo', $compiler->getPath());
    }

    public function testRawTagsCanBeSetToLegacyValues(): void
    {
        $compiler = new BladeCompiler($this->getFiles(), __DIR__);
        $compiler->setEchoFormat('%s');

        $this->assertEquals('<?php echo e($name); ?>', $compiler->compileString('{{{ $name }}}'));
        $this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{{ $name }}'));
        $this->assertEquals('<?php echo $name; ?>', $compiler->compileString('{{
            $name
        }}'));
    }

    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }

    public function testGetTagsProvider()
    {
        return [
            ['{{', '}}'],
            ['{{{', '}}}'],
            ['[[', ']]'],
            ['[[[', ']]]'],
            ['((', '))'],
            ['(((', ')))'],
        ];
    }
}
