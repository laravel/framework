<?php

namespace Illuminate\Tests\View;

use ErrorException;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\ViewException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ViewCompilerEngineTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testViewsMayBeRecompiledAndRendered()
    {
        $engine = $this->getEngine();
        $engine->getCompiler()->shouldReceive('getCompiledPath')->with(__DIR__.'/fixtures/foo.php')->andReturn(__DIR__.'/fixtures/basic.php');
        $engine->getCompiler()->shouldReceive('isExpired')->once()->with(__DIR__.'/fixtures/foo.php')->andReturn(true);
        $engine->getCompiler()->shouldReceive('compile')->once()->with(__DIR__.'/fixtures/foo.php');
        $results = $engine->get(__DIR__.'/fixtures/foo.php');

        $this->assertSame('Hello World
', $results);
    }

    public function testViewsAreNotRecompiledIfTheyAreNotExpired()
    {
        $engine = $this->getEngine();
        $engine->getCompiler()->shouldReceive('getCompiledPath')->with(__DIR__.'/fixtures/foo.php')->andReturn(__DIR__.'/fixtures/basic.php');
        $engine->getCompiler()->shouldReceive('isExpired')->once()->andReturn(false);
        $engine->getCompiler()->shouldReceive('compile')->never();
        $results = $engine->get(__DIR__.'/fixtures/foo.php');

        $this->assertSame('Hello World
', $results);
    }

    public function testThatViewsAreNotAskTwiceIfTheyAreExpired()
    {
        $engine = $this->getEngine();
        $engine->getCompiler()->shouldReceive('getCompiledPath')->with(__DIR__.'/fixtures/foo.php')->andReturn(__DIR__.'/fixtures/basic.php');
        $engine->getCompiler()->shouldReceive('isExpired')->twice()->andReturn(false);
        $engine->getCompiler()->shouldReceive('compile')->never();

        $engine->get(__DIR__.'/fixtures/foo.php');
        $engine->get(__DIR__.'/fixtures/foo.php');
        $engine->get(__DIR__.'/fixtures/foo.php');

        $engine->forgetCompiledOrNotExpired();

        $engine->get(__DIR__.'/fixtures/foo.php');
    }

    public function testViewsAreRecompiledWhenCompiledViewIsMissingViaFileNotFoundException()
    {
        $compiled = __DIR__.'/fixtures/basic.php';
        $path = __DIR__.'/fixtures/foo.php';

        $files = m::mock(Filesystem::class);
        $engine = $this->getEngine($files);

        $files->shouldReceive('getRequire')
            ->once()
            ->with($compiled, [])
            ->andReturn('compiled-content');

        $files->shouldReceive('getRequire')
            ->once()
            ->with($compiled, [])
            ->andThrow(new FileNotFoundException(
                "File does not exist at path {$path}."
            ));

        $files->shouldReceive('getRequire')
            ->once()
            ->with($compiled, [])
            ->andReturn('compiled-content');

        $engine->getCompiler()
            ->shouldReceive('getCompiledPath')
            ->times(3)
            ->with($path)
            ->andReturn($compiled);

        $engine->getCompiler()
            ->shouldReceive('isExpired')
            ->once()
            ->andReturn(true);

        $engine->getCompiler()
            ->shouldReceive('compile')
            ->twice()
            ->with($path);

        $engine->get($path);
        $engine->get($path);
    }

    public function testViewsAreRecompiledWhenCompiledViewIsMissingViaRequireException()
    {
        $compiled = __DIR__.'/fixtures/basic.php';
        $path = __DIR__.'/fixtures/foo.php';

        $files = m::mock(Filesystem::class);
        $engine = $this->getEngine($files);

        $files->shouldReceive('getRequire')
            ->once()
            ->with($compiled, [])
            ->andReturn('compiled-content');

        $files->shouldReceive('getRequire')
            ->once()
            ->with($compiled, [])
            ->andThrow(new ErrorException(
                "require({$path}): Failed to open stream: No such file or directory",
            ));

        $files->shouldReceive('getRequire')
            ->once()
            ->with($compiled, [])
            ->andReturn('compiled-content');

        $engine->getCompiler()
            ->shouldReceive('getCompiledPath')
            ->times(3)
            ->with($path)
            ->andReturn($compiled);

        $engine->getCompiler()
            ->shouldReceive('isExpired')
            ->once()
            ->andReturn(true);

        $engine->getCompiler()
            ->shouldReceive('compile')
            ->twice()
            ->with($path);

        $engine->get($path);
        $engine->get($path);
    }

    public function testViewsAreRecompiledJustOnceWhenCompiledViewIsMissing()
    {
        $compiled = __DIR__.'/fixtures/basic.php';
        $path = __DIR__.'/fixtures/foo.php';

        $files = m::mock(Filesystem::class);
        $engine = $this->getEngine($files);

        $files->shouldReceive('getRequire')
            ->once()
            ->with($compiled, [])
            ->andReturn('compiled-content');

        $files->shouldReceive('getRequire')
            ->once()
            ->with($compiled, [])
            ->andThrow(new FileNotFoundException(
                "File does not exist at path {$path}."
            ));

        $files->shouldReceive('getRequire')
            ->once()
            ->with($compiled, [])
            ->andThrow(new FileNotFoundException(
                "File does not exist at path {$path}."
            ));

        $engine->getCompiler()
            ->shouldReceive('getCompiledPath')
            ->times(3)
            ->with($path)
            ->andReturn($compiled);

        $engine->getCompiler()
            ->shouldReceive('isExpired')
            ->once()
            ->andReturn(true);

        $engine->getCompiler()
            ->shouldReceive('compile')
            ->twice()
            ->with($path);

        $engine->get($path);

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage("File does not exist at path {$path}.");
        $engine->get($path);
    }

    public function testViewsAreNotRecompiledOnRegularViewException()
    {
        $compiled = __DIR__.'/fixtures/basic.php';
        $path = __DIR__.'/fixtures/foo.php';

        $files = m::mock(Filesystem::class);
        $engine = $this->getEngine($files);

        $files->shouldReceive('getRequire')
            ->once()
            ->with($compiled, [])
            ->andThrow(new Exception(
                'Just an regular error...'
            ));

        $engine->getCompiler()
            ->shouldReceive('isExpired')
            ->once()
            ->andReturn(false);

        $engine->getCompiler()
            ->shouldReceive('compile')
            ->never();

        $engine->getCompiler()
            ->shouldReceive('getCompiledPath')
            ->once()
            ->with($path)
            ->andReturn($compiled);

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('Just an regular error...');
        $engine->get($path);
    }

    public function testViewsAreNotRecompiledIfTheyWereJustCompiled()
    {
        $compiled = __DIR__.'/fixtures/basic.php';
        $path = __DIR__.'/fixtures/foo.php';

        $files = m::mock(Filesystem::class);
        $engine = $this->getEngine($files);

        $files->shouldReceive('getRequire')
            ->once()
            ->with($compiled, [])
            ->andThrow(new FileNotFoundException(
                "File does not exist at path {$path}."
            ));

        $engine->getCompiler()
            ->shouldReceive('isExpired')
            ->once()
            ->andReturn(true);

        $engine->getCompiler()
            ->shouldReceive('compile')
            ->once()
            ->with($path);

        $engine->getCompiler()
            ->shouldReceive('getCompiledPath')
            ->once()
            ->with($path)
            ->andReturn($compiled);

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage("File does not exist at path {$path}.");
        $engine->get($path);
    }

    protected function getEngine($filesystem = null)
    {
        return new CompilerEngine(m::mock(CompilerInterface::class), $filesystem ?: new Filesystem);
    }
}
