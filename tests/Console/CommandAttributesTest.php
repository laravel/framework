<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Attributes\Alias;
use Illuminate\Console\Attributes\Hidden;
use Illuminate\Console\Attributes\Isolated;
use Illuminate\Console\Command;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Attribute\AsCommand;

class CommandAttributesTest extends TestCase
{
    public function testHiddenAttribute()
    {
        $command = new CommandWithHiddenAttribute;

        $this->assertTrue($command->isHidden());
    }

    public function testIsolatedAttribute()
    {
        $command = new CommandWithIsolatedAttribute;

        $reflection = new \ReflectionClass($command);
        
        $isolatedProp = $reflection->getProperty('isolated');
        $this->assertTrue($isolatedProp->getValue($command));
        
        $exitCodeProp = $reflection->getProperty('isolatedExitCode');
        $this->assertSame(Command::FAILURE, $exitCodeProp->getValue($command));
    }

    public function testAliasAttribute()
    {
        $command = new CommandWithAliasAttribute;

        $this->assertContains('cmd:alias1', $command->getAliases());
        $this->assertContains('cmd:alias2', $command->getAliases());
    }
}

// Test Command Classes

#[AsCommand(name: 'test:hidden')]
#[Hidden]
class CommandWithHiddenAttribute extends Command
{
    public function handle(): int
    {
        return self::SUCCESS;
    }
}

#[AsCommand(name: 'test:isolated')]
#[Isolated(exitCode: Command::FAILURE)]
class CommandWithIsolatedAttribute extends Command
{
    public function handle(): int
    {
        return self::SUCCESS;
    }
}

#[AsCommand(name: 'test:alias')]
#[Alias('cmd:alias1')]
#[Alias('cmd:alias2')]
class CommandWithAliasAttribute extends Command
{
    public function handle(): int
    {
        return self::SUCCESS;
    }
}
