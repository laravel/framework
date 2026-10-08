<?php

namespace Illuminate\Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use function Illuminate\Support\php_binary;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;

use Symfony\Component\Process\Process;

class SupportComposerTest extends TestCase
{
    use VerifiesDoubles;

    public function testDumpAutoloadRunsTheCorrectCommand()
    {
        $composer = $this->mockComposer(['composer', 'dump-autoload']);

        $composer->dumpAutoloads();
    }

    public function testDumpAutoloadRunsTheCorrectCommandWhenCustomComposerPharIsPresent()
    {
        $expectedProcessArguments = [php_binary(),  'composer.phar', 'dump-autoload'];
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

    private function mockComposer(array $expectedProcessArguments, $customComposerPhar = false, array $environmentVariables = [])
    {
        $directory = __DIR__;

        $files = Double::for(Filesystem::class);
        $files->expects('exists')->with($directory.'/composer.phar')->returns($customComposerPhar);

        $process = Double::for(Process::class);
        $process->expects('run');

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
