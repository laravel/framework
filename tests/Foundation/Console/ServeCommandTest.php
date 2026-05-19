<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Foundation\Console\ServeCommand;
use PHPUnit\Framework\TestCase;

class ServeCommandTest extends TestCase
{
    public function testEnvironmentVariablePassthroughMatchingHonorsPlatformCaseSensitivity()
    {
        $command = new ServeCommandTestCommand;

        $this->assertTrue($command->shouldPassThroughEnvironmentVariable('PATH'));
        $this->assertSame(PHP_OS_FAMILY === 'Windows', $command->shouldPassThroughEnvironmentVariable('Path'));
        $this->assertSame(PHP_OS_FAMILY === 'Windows', $command->shouldPassThroughEnvironmentVariable('SystemRoot'));
        $this->assertFalse($command->shouldPassThroughEnvironmentVariable('ComSpec'));
    }
}

class ServeCommandTestCommand extends ServeCommand
{
    public function shouldPassThroughEnvironmentVariable($key)
    {
        return parent::shouldPassThroughEnvironmentVariable($key);
    }
}
