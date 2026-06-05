<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Foundation\DevCommand;
use PHPUnit\Framework\TestCase;

class FoundationDevCommandTest extends TestCase
{
    public function testNameDefaultsToFirstWordOfCommand()
    {
        $command = new DevCommand('php artisan serve');

        $this->assertSame('php', $command->name());
    }

    public function testNameCanBeExplicitlySet()
    {
        $command = new DevCommand('php artisan serve', 'server');

        $this->assertSame('server', $command->name());
    }

    public function testToArrayReturnsCommandDetails()
    {
        $command = new DevCommand('php artisan serve', 'server');

        $this->assertSame([
            'command' => 'php artisan serve',
            'name' => 'server',
            'color' => null,
        ], $command->toArray());
    }

    public function testColorCanBeSet()
    {
        $command = new DevCommand('php artisan serve', 'server');
        $result = $command->color('#ff0000');

        $this->assertSame($command, $result);
        $this->assertSame('#ff0000', $command->toArray()['color']);
    }

    public function testBlueColor()
    {
        $command = new DevCommand('cmd', 'test');
        $result = $command->blue();

        $this->assertSame($command, $result);
        $this->assertSame(DevCommand::BLUE, $command->toArray()['color']);
    }

    public function testPurpleColor()
    {
        $command = new DevCommand('cmd', 'test');
        $command->purple();

        $this->assertSame(DevCommand::PURPLE, $command->toArray()['color']);
    }

    public function testPinkColor()
    {
        $command = new DevCommand('cmd', 'test');
        $command->pink();

        $this->assertSame(DevCommand::PINK, $command->toArray()['color']);
    }

    public function testOrangeColor()
    {
        $command = new DevCommand('cmd', 'test');
        $command->orange();

        $this->assertSame(DevCommand::ORANGE, $command->toArray()['color']);
    }

    public function testGreenColor()
    {
        $command = new DevCommand('cmd', 'test');
        $command->green();

        $this->assertSame(DevCommand::GREEN, $command->toArray()['color']);
    }

    public function testYellowColor()
    {
        $command = new DevCommand('cmd', 'test');
        $command->yellow();

        $this->assertSame(DevCommand::YELLOW, $command->toArray()['color']);
    }

    public function testColorMethodsAreFluent()
    {
        $command = new DevCommand('cmd', 'test');

        $this->assertSame($command, $command->blue());
        $this->assertSame($command, $command->purple());
        $this->assertSame($command, $command->pink());
        $this->assertSame($command, $command->orange());
        $this->assertSame($command, $command->green());
        $this->assertSame($command, $command->yellow());
    }
}
