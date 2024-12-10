<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\ListFailedCommand;
use Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;

class ListFailedCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testGetAllFailedJobs()
    {
        $container = new Application;
        $container->instance('queue.failer', $provider = m::spy(DatabaseUuidFailedJobProvider::class));

        $command = new ListFailedCommand();
        $command->setLaravel($container);

        $command->run(new ArrayInput([]), new NullOutput());

        $provider->shouldHaveReceived('all')->once();
    }

    public function testLimitANumberOfLatestFailedJobs()
    {
        $container = new Application;
        $container->instance('queue.failer', $provider = m::spy(DatabaseUuidFailedJobProvider::class));

        $command = new ListFailedCommand();
        $command->setLaravel($container);

        $command->run(new ArrayInput(['--limit' => 10]), new NullOutput());

        $provider->shouldHaveReceived('limit')->once();
    }
}
