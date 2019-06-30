<?php

namespace Illuminate\Tests\Notifications;

use Mockery as m;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Illuminate\Foundation\Console\ChannelMakeCommand;

class NotificationChannelMakeCommandTest extends TestCase
{
    public function testBasicBadNameThrowsException()
    {
        $command = new ChannelMakeCommand(
            $files = m::mock(Filesystem::class)
        );
        $app = m::mock(Application::class)->makePartial();

        $app->shouldReceive('getNamespace')->andReturn('App\\');

        $command->setLaravel($app);
        $this->expectException(InvalidArgumentException::class);

        $this->runCommand($command, ['name' => 'bad_migration,name!']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}
