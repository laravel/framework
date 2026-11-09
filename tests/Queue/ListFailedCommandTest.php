<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\ListFailedCommand;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ListFailedCommandTest extends TestCase
{
    use VerifiesDoubles;

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

    public function testItFiltersFailedJobsByQueue()
    {
        $payload = json_encode([
            'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            'data' => [
                'command' => 'O:32:"Illuminate\Tests\Queue\ExampleJob":0:{}',
            ],
        ]);

        $output = $this->runCommandWithFailedJobs([
            (object) [
                'id' => 'default-job-id',
                'connection' => 'redis',
                'queue' => 'default',
                'payload' => $payload,
                'exception' => 'Exception stack trace',
                'failed_at' => '2026-05-18 12:00:00',
            ],
            (object) [
                'id' => 'emails-job-id',
                'connection' => 'redis',
                'queue' => 'emails',
                'payload' => $payload,
                'exception' => 'Exception stack trace',
                'failed_at' => '2026-05-18 12:05:00',
            ],
        ], ['--json' => true, '--queue' => 'emails']);

        $this->assertJson($output);
        $this->assertJsonStringEqualsJsonString(json_encode([
            [
                'id' => 'emails-job-id',
                'connection' => 'redis',
                'queue' => 'emails',
                'class' => 'Illuminate\Tests\Queue\ExampleJob',
                'failed_at' => '2026-05-18 12:05:00',
            ],
        ]), $output);
    }

    public function testItSummarizesFailedJobsAsJson()
    {
        $output = $this->runCommandWithFailedJobs([
            $this->failedJob('1', 'default', 'App\\Jobs\\SendEmail', '2026-05-18 12:00:00'),
            $this->failedJob('2', 'default', 'App\\Jobs\\ProcessPodcast', '2026-05-18 09:00:00'),
            $this->failedJob('3', 'default', 'App\\Jobs\\SendEmail', '2026-05-17 08:30:00'),
            $this->failedJob('4', 'default', 'App\\Jobs\\SendEmail', '2026-05-19 18:45:00'),
            $this->failedJob('5', 'emails', 'App\\Jobs\\SendEmail', '2026-05-18 10:00:00'),
        ], ['--summary' => true, '--json' => true]);

        $this->assertJsonStringEqualsJsonString(json_encode([
            [
                'first_failed_at' => '2026-05-17 08:30:00',
                'last_failed_at' => '2026-05-19 18:45:00',
                'connection' => 'redis',
                'queue' => 'default',
                'class' => 'App\\Jobs\\SendEmail',
                'count' => 3,
            ],
            [
                'first_failed_at' => '2026-05-18 10:00:00',
                'last_failed_at' => '2026-05-18 10:00:00',
                'connection' => 'redis',
                'queue' => 'emails',
                'class' => 'App\\Jobs\\SendEmail',
                'count' => 1,
            ],
            [
                'first_failed_at' => '2026-05-18 09:00:00',
                'last_failed_at' => '2026-05-18 09:00:00',
                'connection' => 'redis',
                'queue' => 'default',
                'class' => 'App\\Jobs\\ProcessPodcast',
                'count' => 1,
            ],
        ]), $output);
    }

    public function testItSummarizesFailedJobsAsTable()
    {
        $output = $this->runCommandWithFailedJobs([
            $this->failedJob('1', 'default', 'App\\Jobs\\SendEmail', '2026-05-18 12:00:00'),
            $this->failedJob('2', 'default', 'App\\Jobs\\SendEmail', '2026-05-17 08:30:00'),
        ], ['--summary' => true]);

        $this->assertStringContainsString('First Occurrence', $output);
        $this->assertStringContainsString('Last Occurrence', $output);
        $this->assertStringContainsString('Connection@Queue', $output);
        $this->assertMatchesRegularExpression(
            '/2026-05-17 08:30:00\s*\|\s*2026-05-18 12:00:00\s*\|\s*redis@default\s*\|\s*App\\\\Jobs\\\\SendEmail\s*\|\s+2 \|/',
            $output
        );
    }

    public function testItSummarizesFailedJobsForTheGivenQueue()
    {
        $output = $this->runCommandWithFailedJobs([
            $this->failedJob('1', 'default', 'App\\Jobs\\SendEmail', '2026-05-18 12:00:00'),
            $this->failedJob('2', 'emails', 'App\\Jobs\\SendEmail', '2026-05-18 13:00:00'),
        ], ['--summary' => true, '--json' => true, '--queue' => 'emails']);

        $this->assertJsonStringEqualsJsonString(json_encode([
            [
                'first_failed_at' => '2026-05-18 13:00:00',
                'last_failed_at' => '2026-05-18 13:00:00',
                'connection' => 'redis',
                'queue' => 'emails',
                'class' => 'App\\Jobs\\SendEmail',
                'count' => 1,
            ],
        ]), $output);
    }

    public function testItSummarizesFailedJobsPerConnection()
    {
        $output = $this->runCommandWithFailedJobs([
            $this->failedJob('1', 'default', 'App\\Jobs\\SendEmail', '2026-05-18 12:00:00', 'redis'),
            $this->failedJob('2', 'default', 'App\\Jobs\\SendEmail', '2026-05-18 11:00:00', 'sqs'),
        ], ['--summary' => true]);

        $this->assertStringContainsString('redis@default', $output);
        $this->assertStringContainsString('sqs@default', $output);
    }

    public function testItSummarizesEmptyFailedJobs()
    {
        $this->assertJsonStringEqualsJsonString('[]', $this->runCommandWithFailedJobs([], ['--summary' => true, '--json' => true]));
        $this->assertStringContainsString('No failed jobs found.', $this->runCommandWithFailedJobs([], ['--summary' => true]));
    }

    protected function failedJob(string $id, string $queue, string $class, string $failedAt, string $connection = 'redis'): object
    {
        return (object) [
            'id' => $id,
            'connection' => $connection,
            'queue' => $queue,
            'payload' => json_encode([
                'displayName' => $class,
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'data' => ['command' => 'O:8:"'.$class.'":0:{}'],
            ]),
            'exception' => 'Exception stack trace',
            'failed_at' => $failedAt,
        ];
    }

    protected function runCommandWithFailedJobs(array $failedJobs, array $arguments = []): string
    {
        $container = new Application;
        $failer = Double::for(FailedJobProviderInterface::class);
        $container->instance('queue.failer', $failer);

        $failer->expects('all')->returns($failedJobs);

        $command = new ListFailedCommand;
        $command->setLaravel($container);

        $output = new BufferedOutput;

        $command->run(new ArrayInput($arguments), $output);

        return $output->fetch();
    }
}
