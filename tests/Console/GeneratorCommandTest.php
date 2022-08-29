<?php

namespace Illuminate\Tests\Console;

use Illuminate\Config\Repository;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Foundation\Application;
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
            [$this->createStub(Filesystem::class)],
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
            [$this->createStub(Filesystem::class)],
            'FooMakeCommand'
        );
        $sut->setLaravel(app());

        $input = new ArrayInput(['name' => 'class']);
        $output = new NullOutput();

        $this->assertSame(Command::FAILURE, $sut->run($input, $output));
    }

    /**
     * @test
     *
     * @throws FileNotFoundException
     */
    public function itShouldGenerateTheRequestedClass(): void
    {
        $appPath = '/path/to/app';
        $nameArgument = 'MyFoo';

        $fileSystem = $this->createStub(Filesystem::class);
        $stub = '<?php namespace DummyNamespace; class DummyClass {}';
        $fileSystem->method('get')->willReturn($stub);

        // @phpstan-ignore-next-line
        $fileSystem->expects($this->once())->method('put')->with(
            sprintf('%s//%s.php', $appPath, $nameArgument),
            '<?php namespace App; class MyFoo {}'
        )->willReturn(0);

        $sut = $this->getMockForAbstractClass(
            GeneratorCommand::class,
            [$fileSystem],
            'FooMakeCommand'
        );

        $laravel = $this->createStub(Application::class);
        $config = new Repository();
        $config->set('auth', [
            'defaults'=> ['guard' => 'web'],
            'guards' => ['web' => ['provider' => 'users']],
            'providers' => ['users' => ['model' => 'App\User']],
        ]);

        $laravel->method('make')->willReturnOnConsecutiveCalls(
            $this->createStub(OutputStyle::class),
            $this->createStub(Factory::class),
            $appPath,
            null,
            $config,
            $config,
            $config,
        );
        $laravel->method('getNamespace')->willReturn('App');

        $sut->setLaravel($laravel);

        $input = new ArrayInput(['name' => 'MyFoo']);
        $output = new NullOutput();

        $sut->run($input, $output);

        $this->assertSame(Command::SUCCESS, $sut->handle());
    }
}
