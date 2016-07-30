<?php

use Mockery as m;

class FoundationComposerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testDumpAutoloadRunsTheCorrectCommand()
    {
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';

        $composer = $this->getMockBuilder('Illuminate\Support\Composer')->setMethods(['getProcess'])->setConstructorArgs([$files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__])->getMock();
        $files->shouldReceive('exists')->once()->with(__DIR__.'/composer.phar')->andReturn(true);
        $process = m::mock('stdClass');
        $composer->expects($this->once())->method('getProcess')->will($this->returnValue($process));
        $process->shouldReceive('setCommandLine')->once()->with($escape.PHP_BINARY.$escape.(defined('HHVM_VERSION') ? ' --php' : '').' composer.phar dump-autoload');
        $process->shouldReceive('run')->once();

        $composer->dumpAutoloads();
    }

    public function testDumpAutoloadRunsTheCorrectCommandWhenComposerIsntPresent()
    {
        $composer = $this->getMockBuilder('Illuminate\Support\Composer')->setMethods(['getProcess'])->setConstructorArgs([$files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__])->getMock();
        $files->shouldReceive('exists')->once()->with(__DIR__.'/composer.phar')->andReturn(false);
        $process = m::mock('stdClass');
        $composer->expects($this->once())->method('getProcess')->will($this->returnValue($process));
        $process->shouldReceive('setCommandLine')->once()->with('composer dump-autoload');
        $process->shouldReceive('run')->once();

        $composer->dumpAutoloads();
    }
}
