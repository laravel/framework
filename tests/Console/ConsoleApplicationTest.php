<?php

use Mockery as m;

class ConsoleApplicationTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testAddSetsLaravelInstance()
    {
        $app = $this->getMockConsole(['addToParent']);
        $command = m::mock('Illuminate\Console\Command');
        $command->shouldReceive('setLaravel')->once()->with(m::type('Illuminate\Contracts\Foundation\Application'));
        $app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->will($this->returnValue($command));
        $result = $app->add($command);

        $this->assertEquals($command, $result);
    }

    public function testLaravelNotSetOnSymfonyCommands()
    {
        $app = $this->getMockConsole(['addToParent']);
        $command = m::mock('Symfony\Component\Console\Command\Command');
        $command->shouldReceive('setLaravel')->never();
        $app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->will($this->returnValue($command));
        $result = $app->add($command);

        $this->assertEquals($command, $result);
    }

    public function testResolveAddsCommandViaApplicationResolution()
    {
        $app = $this->getMockConsole(['addToParent']);
        $command = m::mock('Symfony\Component\Console\Command\Command');
        $app->getLaravel()->shouldReceive('make')->once()->with('foo')->andReturn(m::mock('Symfony\Component\Console\Command\Command'));
        $app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->will($this->returnValue($command));
        $result = $app->resolve('foo');

        $this->assertEquals($command, $result);
    }

    protected function getMockConsole(array $methods)
    {
        $app = m::mock('Illuminate\Contracts\Foundation\Application', ['version' => '5.3']);
        $events = m::mock('Illuminate\Contracts\Events\Dispatcher', ['fire' => null]);

        $console = $this->getMock('Illuminate\Console\Application', $methods, [
            $app, $events, 'test-version',
        ]);

        return $console;
    }
}
