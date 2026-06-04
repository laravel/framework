<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\ListFailedCommand;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ListFailedCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testItDisplaysEmptyFailedJobsAsJson()
    {
        $output = $this->runCommandWithFailedJobs([], ['--json' => true]);

        $this->assertJson($output);
        $this->assertJsonStringEqualsJsonString('[]', $output);
    }

    public function testItDisplaysFailedJobsAsJson()
    {
        $output = $this->runCommandWithFailedJobs([
            (object) [
                'id' => 'failed-job-id',
                'connection' => 'redis',
                'queue' => 'default',
                'payload' => json_encode([
                    'job' => 'Illuminate\Queue\CallQueuedHandler@call',
                    'data' => [
                        'command' => 'O:32:"Illuminate\Tests\Queue\ExampleJob":0:{}',
                    ],
                ]),
                'exception' => 'Exception stack trace',
                'failed_at' => '2026-05-18 12:00:00',
            ],
        ], ['--json' => true]);

        $this->assertJson($output);
        $this->assertJsonStringEqualsJsonString(json_encode([
            [
                'id' => 'failed-job-id',
                'connection' => 'redis',
                'queue' => 'default',
                'class' => 'Illuminate\Tests\Queue\ExampleJob',
                'failed_at' => '2026-05-18 12:00:00',
            ],
        ]), $output);
    }

    protected function runCommandWithFailedJobs(array $failedJobs, array $arguments = []): string
    {
        $container = new Application;
        $container->instance('queue.failer', $failer = m::mock());

        $failer->shouldReceive('all')->once()->andReturn($failedJobs);

        $command = new ListFailedCommand;
        $command->setLaravel($container);

        $output = new BufferedOutput;

        $command->run(new ArrayInput($arguments), $output);

        return $output->fetch();
    }
}
