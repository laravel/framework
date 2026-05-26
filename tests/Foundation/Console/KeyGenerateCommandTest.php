<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Container\Container;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use Illuminate\Testing\Assert;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class KeyGenerateCommandTest extends TestCase
{
    public function testProhibitable()
    {
        $input = new ArrayInput([]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);

        $container = m::mock(Container::class);
        $container->shouldReceive('call');
        $container->shouldReceive('runningUnitTests')->andReturn(true);
        $container->shouldReceive('make')->with(OutputStyle::class, m::any())->andReturn($outputStyle);
        $container->shouldReceive('make')->with(Factory::class, m::any())->andReturn(new Factory($outputStyle));

        $command = new KeyGenerateCommand;
        $command->setLaravel($container);

        $command->run($input, $output);

        KeyGenerateCommand::prohibit();

        Assert::assertSame(Command::FAILURE, $command->handle());
    }

    protected function tearDown(): void
    {
        KeyGenerateCommand::prohibit(false);

        parent::tearDown();
    }
}
