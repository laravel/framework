<?php

namespace Illuminate\Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Process\PendingProcess;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Composer;
use Mockery as m;
use PHPUnit\Framework\TestCase;

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

    public function testRequirePackagesRunsTheCorrectCommand()
    {
        $composer = $this->mockComposer(['composer', 'require', 'pestphp/pest:^2.0', 'pestphp/pest-plugin-laravel:^2.0', '--dev']);

        $composer->requirePackages(['pestphp/pest:^2.0', 'pestphp/pest-plugin-laravel:^2.0'], true);
    }

    public function testRemovePackagesRunsTheCorrectCommand()
    {
        $composer = $this->mockComposer(['composer', 'remove', 'phpunit/phpunit', '--dev']);

        $composer->removePackages(['phpunit/phpunit'], true);
    }

    public function testGetVersionRunsTheCorrectCommand()
    {
        $composer = $this->mockComposer(['composer', '-V', '--no-ansi']);

        $composer->getVersion();
    }

    private function mockComposer(array $expectedProcessArguments, $customComposerPhar = false, array $environmentVariables = [])
    {
        $directory = __DIR__;

        $files = m::mock(Filesystem::class);
        $files->shouldReceive('exists')->once()->with($directory.'/composer.phar')->andReturn($customComposerPhar);

        $processResult = m::mock(ProcessResult::class);
        $processResult->shouldReceive('exitCode');
        $processResult->shouldReceive('successful');
        $processResult->shouldReceive('output');
        $process = m::mock(PendingProcess::class);
        $process->shouldReceive('run')->once()->andReturn($processResult);

        $composer = $this->getMockBuilder(Composer::class)
            ->onlyMethods(['getProcess'])
            ->setConstructorArgs([$files, $directory, $environmentVariables])
            ->getMock();
        $composer->expects($this->once())
            ->method('getProcess')
            ->with($expectedProcessArguments)
            ->willReturn($process);

        return $composer;
    }
}
