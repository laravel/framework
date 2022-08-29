<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class GeneratorCommandTest extends TestCase
{
    /**
     * @test
     */
    public function itShouldThrowIfNameIsNotPassedAsArgument(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "name").');

        $sut = $this->getMockForAbstractClass(
            GeneratorCommand::class,
            [new Filesystem()],
            'FooMakeCommand'
        );
        $sut->setLaravel(app());

        $input = new ArrayInput([]);
        $output = new NullOutput();

        $sut->run($input, $output);
    }

    /**
     * @test
     */
    public function itShouldFailIfNameArgumentIsReservedName(): void
    {
        $sut = $this->getMockForAbstractClass(
            GeneratorCommand::class,
            [new Filesystem()],
            'FooMakeCommand'
        );
        $sut->setLaravel(app());

        $input = new ArrayInput(['name' => 'class']);
        $output = new NullOutput();

        $this->assertSame(Command::FAILURE, $sut->run($input, $output));
    }
}
