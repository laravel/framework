<?php

namespace Illuminate\Tests\Foundation;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;

class FoundationComposerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testDumpAutoloadRunsTheCorrectCommand()
    {
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';

        $composer = $this->getMockBuilder(Composer::class)->setMethods(['getProcess'])->setConstructorArgs([$files = m::mock(Filesystem::class), __DIR__])->getMock();
        $files->shouldReceive('exists')->once()->with(__DIR__.'/composer.phar')->andReturn(true);
        $process = m::mock(stdClass::class);
        $composer->expects($this->once())->method('getProcess')->will($this->returnValue($process));
        $process->shouldReceive('setCommandLine')->once()->with($escape.PHP_BINARY.$escape.' composer.phar dump-autoload');
        $process->shouldReceive('run')->once();

        $composer->dumpAutoloads();
    }

    public function testDumpAutoloadRunsTheCorrectCommandWhenComposerIsntPresent()
    {
        $composer = $this->getMockBuilder(Composer::class)->setMethods(['getProcess'])->setConstructorArgs([$files = m::mock(Filesystem::class), __DIR__])->getMock();
        $files->shouldReceive('exists')->once()->with(__DIR__.'/composer.phar')->andReturn(false);
        $process = m::mock(stdClass::class);
        $composer->expects($this->once())->method('getProcess')->will($this->returnValue($process));
        $process->shouldReceive('setCommandLine')->once()->with('composer dump-autoload');
        $process->shouldReceive('run')->once();

        $composer->dumpAutoloads();
    }
}
