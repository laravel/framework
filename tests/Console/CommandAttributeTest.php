<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Attribute\AsCommand;

class CommandAttributeTest extends TestCase
{
    public function testCommandWithAttribute()
    {
        $command = new TestCommandWithAttribute();
        $this->assertEquals('test:command', $command->getName());
        $this->assertEquals('This is a test command', $command->getDescription());
    }

    public function testCommandWithoutAttribute()
    {
        $command = new TestCommandWithoutAttribute();
        $this->assertEquals('test:no-attribute', $command->getName());
        $this->assertEquals('', $command->getDescription());
    }
}

#[AsCommand(name: 'test:command', description: 'This is a test command')]
class TestCommandWithAttribute extends Command
{
    public function handle()
    {
        return 0;
    }
}

class TestCommandWithoutAttribute extends Command
{
    protected $name = 'test:no-attribute';

    public function handle()
    {
        return 0;
    }
}
