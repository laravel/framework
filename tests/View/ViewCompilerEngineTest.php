<?php

namespace Illuminate\Tests\View;

use ErrorException;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\ViewException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ViewCompilerEngineTest extends TestCase
{
    public function testViewsMayBeRecompiledAndRendered()
    {
        $engine = $this->getEngine();
        $engine->getCompiler()->expects('getCompiledPath')->with(__DIR__.'/fixtures/foo.php')->andReturn(__DIR__.'/fixtures/basic.php');
        $engine->getCompiler()->expects('isExpired')->with(__DIR__.'/fixtures/foo.php')->andReturn(true);
        $engine->getCompiler()->expects('compile')->with(__DIR__.'/fixtures/foo.php');
        $results = $engine->get(__DIR__.'/fixtures/foo.php');

        $this->assertSame('Hello World
', $results);
    }

    public function testViewsAreNotRecompiledIfTheyAreNotExpired()
    {
        $engine = $this->getEngine();
        $engine->getCompiler()->expects('getCompiledPath')->with(__DIR__.'/fixtures/foo.php')->andReturn(__DIR__.'/fixtures/basic.php');
        $engine->getCompiler()->expects('isExpired')->andReturn(false);
        $engine->getCompiler()->shouldReceive('compile')->never();
        $results = $engine->get(__DIR__.'/fixtures/foo.php');

        $this->assertSame('Hello World
', $results);
    }

    public function testRegularExceptionsAreReThrownAsViewExceptions()
    {
        $engine = $this->getEngine();
        $engine->getCompiler()->expects('getCompiledPath')->with(__DIR__.'/fixtures/foo.php')->andReturn(__DIR__.'/fixtures/regular-exception.php');
        $engine->getCompiler()->expects('isExpired')->andReturn(false);

        $this->expectExceptionObject(new ViewException('regular exception message'));

        $engine->get(__DIR__.'/fixtures/foo.php');
    }

    public function testHttpExceptionsAreNotReThrownAsViewExceptions()
    {
        $engine = $this->getEngine();
        $engine->getCompiler()->expects('getCompiledPath')->with(__DIR__.'/fixtures/foo.php')->andReturn(__DIR__.'/fixtures/http-exception.php');
        $engine->getCompiler()->expects('isExpired')->andReturn(false);

        $this->expectExceptionObject(new HttpException(403, 'http exception message'));

        $engine->get(__DIR__.'/fixtures/foo.php');
    }

    public function testThatViewsAreNotAskTwiceIfTheyAreExpired()
    {
        $engine = $this->getEngine();
        $engine->getCompiler()->expects('getCompiledPath')->times(4)->with(__DIR__.'/fixtures/foo.php')->andReturn(__DIR__.'/fixtures/basic.php');
        $engine->getCompiler()->expects('isExpired')->times(2)->andReturn(false);
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

        $files = Mockery::mock(Filesystem::class);
        $engine = $this->getEngine($files);

        $files->expects('getRequire')
            ->with($compiled, [])
            ->andReturn('compiled-content');

        $files->expects('getRequire')
            ->with($compiled, [])
            ->andThrow(new FileNotFoundException(
                "File does not exist at path {$path}."
            ));

        $files->expects('getRequire')
            ->with($compiled, [])
            ->andReturn('compiled-content');

        $engine->getCompiler()
            ->expects('getCompiledPath')
            ->times(3)
            ->with($path)
            ->andReturn($compiled);

        $engine->getCompiler()
            ->expects('isExpired')
            ->andReturn(true);

        $engine->getCompiler()
            ->expects('compile')
            ->times(2)
            ->with($path);

        $engine->get($path);
        $engine->get($path);
    }

    public function testViewsAreRecompiledWhenCompiledViewIsMissingViaRequireException()
    {
        $compiled = __DIR__.'/fixtures/basic.php';
        $path = __DIR__.'/fixtures/foo.php';

        $files = Mockery::mock(Filesystem::class);
        $engine = $this->getEngine($files);

        $files->expects('getRequire')
            ->with($compiled, [])
            ->andReturn('compiled-content');

        $files->expects('getRequire')
            ->with($compiled, [])
            ->andThrow(new ErrorException(
                "require({$path}): Failed to open stream: No such file or directory",
            ));

        $files->expects('getRequire')
            ->with($compiled, [])
            ->andReturn('compiled-content');

        $engine->getCompiler()
            ->expects('getCompiledPath')
            ->times(3)
            ->with($path)
            ->andReturn($compiled);

        $engine->getCompiler()
            ->expects('isExpired')
            ->andReturn(true);

        $engine->getCompiler()
            ->expects('compile')
            ->times(2)
            ->with($path);

        $engine->get($path);
        $engine->get($path);
    }

    public function testViewsAreRecompiledJustOnceWhenCompiledViewIsMissing()
    {
        $compiled = __DIR__.'/fixtures/basic.php';
        $path = __DIR__.'/fixtures/foo.php';

        $files = Mockery::mock(Filesystem::class);
        $engine = $this->getEngine($files);

        $files->expects('getRequire')
            ->with($compiled, [])
            ->andReturn('compiled-content');

        $files->expects('getRequire')
            ->with($compiled, [])
            ->andThrow(new FileNotFoundException(
                "File does not exist at path {$path}."
            ));

        $files->expects('getRequire')
            ->with($compiled, [])
            ->andThrow(new FileNotFoundException(
                "File does not exist at path {$path}."
            ));

        $engine->getCompiler()
            ->expects('getCompiledPath')
            ->times(3)
            ->with($path)
            ->andReturn($compiled);

        $engine->getCompiler()
            ->expects('isExpired')
            ->andReturn(true);

        $engine->getCompiler()
            ->expects('compile')
            ->times(2)
            ->with($path);

        $engine->get($path);

        $this->expectExceptionObject(new ViewException("File does not exist at path {$path}."));
        $engine->get($path);
    }

    public function testViewsAreNotRecompiledOnRegularViewException()
    {
        $compiled = __DIR__.'/fixtures/basic.php';
        $path = __DIR__.'/fixtures/foo.php';

        $files = Mockery::mock(Filesystem::class);
        $engine = $this->getEngine($files);

        $files->expects('getRequire')
            ->with($compiled, [])
            ->andThrow(new Exception(
                'Just an regular error...'
            ));

        $engine->getCompiler()
            ->expects('isExpired')
            ->andReturn(false);

        $engine->getCompiler()
            ->shouldReceive('compile')
            ->never();

        $engine->getCompiler()
            ->expects('getCompiledPath')
            ->with($path)
            ->andReturn($compiled);

        $this->expectExceptionObject(new ViewException('Just an regular error...'));
        $engine->get($path);
    }

    public function testViewsAreNotRecompiledIfTheyWereJustCompiled()
    {
        $compiled = __DIR__.'/fixtures/basic.php';
        $path = __DIR__.'/fixtures/foo.php';

        $files = Mockery::mock(Filesystem::class);
        $engine = $this->getEngine($files);

        $files->expects('getRequire')
            ->with($compiled, [])
            ->andThrow(new FileNotFoundException(
                "File does not exist at path {$path}."
            ));

        $engine->getCompiler()
            ->expects('isExpired')
            ->andReturn(true);

        $engine->getCompiler()
            ->expects('compile')
            ->with($path);

        $engine->getCompiler()
            ->expects('getCompiledPath')
            ->with($path)
            ->andReturn($compiled);

        $this->expectExceptionObject(new ViewException("File does not exist at path {$path}."));
        $engine->get($path);
    }

    protected function getEngine($filesystem = null)
    {
        return new CompilerEngine(Mockery::mock(CompilerInterface::class), $filesystem ?: new Filesystem);
    }
}
