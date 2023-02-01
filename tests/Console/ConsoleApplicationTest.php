<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Tests\Console\Fixtures\FakeCommandWithInputPrompting;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ConsoleApplicationTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testAddSetsLaravelInstance()
    {
        $app = $this->getMockConsole(['addToParent']);
        $command = m::mock(Command::class);
        $command->shouldReceive('setLaravel')->once()->with(m::type(ApplicationContract::class));
        $app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->willReturn($command);
        $result = $app->add($command);

        $this->assertEquals($command, $result);
    }

    public function testLaravelNotSetOnSymfonyCommands()
    {
        $app = $this->getMockConsole(['addToParent']);
        $command = m::mock(SymfonyCommand::class);
        $command->shouldReceive('setLaravel')->never();
        $app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->willReturn($command);
        $result = $app->add($command);

        $this->assertEquals($command, $result);
    }

    public function testResolveAddsCommandViaApplicationResolution()
    {
        $app = $this->getMockConsole(['addToParent']);
        $command = m::mock(SymfonyCommand::class);
        $app->getLaravel()->shouldReceive('make')->once()->with('foo')->andReturn(m::mock(SymfonyCommand::class));
        $app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->willReturn($command);
        $result = $app->resolve('foo');

        $this->assertEquals($command, $result);
    }

    public function testCallFullyStringCommandLine()
    {
        $app = new Application(
            $app = m::mock(ApplicationContract::class, ['version' => '6.0']),
            $events = m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );

        $codeOfCallingArrayInput = $app->call('help', [
            '--raw' => true,
            '--format' => 'txt',
            '--no-interaction' => true,
            '--env' => 'testing',
        ]);

        $outputOfCallingArrayInput = $app->output();

        $codeOfCallingStringInput = $app->call(
            'help --raw --format=txt --no-interaction --env=testing'
        );

        $outputOfCallingStringInput = $app->output();

        $this->assertSame($codeOfCallingArrayInput, $codeOfCallingStringInput);
        $this->assertSame($outputOfCallingArrayInput, $outputOfCallingStringInput);
    }

    public function testCommandInputPromptingWorksCorrectly()
    {
        $app = new Application(
            $app = new \Illuminate\Foundation\Application(__DIR__),
            $events = m::mock(Dispatcher::class, ['dispatch' => null, 'fire' => null]),
            'testing'
        );

        $app->addCommands([new FakeCommandWithInputPrompting(false)]);

        $statusCode = $app->call('fake-command-for-testing', [
            'name' => 'foo',
        ]);

        $this->assertSame(0, $statusCode);
    }

    protected function getMockConsole(array $methods)
    {
        $app = m::mock(ApplicationContract::class, ['version' => '6.0']);
        $events = m::mock(Dispatcher::class, ['dispatch' => null]);

        return $this->getMockBuilder(Application::class)->onlyMethods($methods)->setConstructorArgs([
            $app, $events, 'test-version',
        ])->getMock();
    }
}
