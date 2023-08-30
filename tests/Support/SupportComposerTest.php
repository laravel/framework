<?php

namespace Illuminate\Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class SupportComposerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testDumpAutoloadRunsTheCorrectCommand()
    {
        $composer = $this->mockComposer(['composer', 'dump-autoload']);

        $composer->dumpAutoloads();
    }

    public function testDumpAutoloadRunsTheCorrectCommandWhenCustomComposerPharIsPresent()
    {
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';

        $expectedProcessArguments = [$escape.PHP_BINARY.$escape,  'composer.phar', 'dump-autoload'];
        $customComposerPhar = true;

        $composer = $this->mockComposer($expectedProcessArguments, $customComposerPhar);

        $composer->dumpAutoloads();
    }

    public function testDumpAutoloadRunsTheCorrectCommandWithExtraArguments()
    {
        $composer = $this->mockComposer(['composer', 'dump-autoload', '--no-scripts']);

        $composer->dumpAutoloads('--no-scripts');
    }

    public function testDumpOptimizedTheCorrectCommand()
    {
        $composer = $this->mockComposer(['composer', 'dump-autoload', '--optimize']);

        $composer->dumpOptimized();
    }

    private function mockComposer(array $expectedProcessArguments, $customComposerPhar = false)
    {
        $directory = __DIR__;

        $files = m::mock(Filesystem::class);
        $files->shouldReceive('exists')->once()->with($directory.'/composer.phar')->andReturn($customComposerPhar);

        $process = m::mock(Process::class);
        $process->shouldReceive('run')->once();

        $composer = $this->getMockBuilder(Composer::class)
            ->onlyMethods(['getProcess'])
            ->setConstructorArgs([$files, $directory])
            ->getMock();
        $composer->expects($this->once())
            ->method('getProcess')
            ->with($expectedProcessArguments)
            ->willReturn($process);

        return $composer;
    }
}
