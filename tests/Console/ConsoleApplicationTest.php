<?php

namespace Illuminate\Tests\Console;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Command\Command;
use Illuminate\Contracts\Foundation\Application;

class ConsoleApplicationTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testAddSetsLaravelInstance()
    {
        $app = $this->getMockConsole(['addToParent']);
        $command = m::mock(\Illuminate\Console\Command::class);
        $command->shouldReceive('setLaravel')->once()->with(m::type(Application::class));
        $app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->will($this->returnValue($command));
        $result = $app->add($command);

        $this->assertEquals($command, $result);
    }

    public function testLaravelNotSetOnSymfonyCommands()
    {
        $app = $this->getMockConsole(['addToParent']);
        $command = m::mock(Command::class);
        $command->shouldReceive('setLaravel')->never();
        $app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->will($this->returnValue($command));
        $result = $app->add($command);

        $this->assertEquals($command, $result);
    }

    public function testResolveAddsCommandViaApplicationResolution()
    {
        $app = $this->getMockConsole(['addToParent']);
        $command = m::mock(Command::class);
        $app->getLaravel()->shouldReceive('make')->once()->with('foo')->andReturn(m::mock(Command::class));
        $app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->will($this->returnValue($command));
        $result = $app->resolve('foo');

        $this->assertEquals($command, $result);
    }

    protected function getMockConsole(array $methods)
    {
        $app = m::mock(Application::class, ['version' => '5.7']);
        $events = m::mock(Dispatcher::class, ['dispatch' => null]);

        return $this->getMockBuilder(\Illuminate\Console\Application::class)->setMethods($methods)->setConstructorArgs([
            $app, $events, 'test-version',
        ])->getMock();
    }
}
