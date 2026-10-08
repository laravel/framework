<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\BatchRepository;
use Illuminate\Console\Command;
use Illuminate\Console\CommandMutex;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\RetryBatchCommand;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RetryBatchCommandTest extends TestCase
{
    use VerifiesDoubles;

    protected function tearDown(): void
    {
    }

    public function testItFailsWhenTheBatchCannotBeFound()
    {
        $container = new Application;
        $repository = Double::for(BatchRepository::class);
        $repository->expects('find')->with('missing-batch-id')->returns(null);
        $container->instance(BatchRepository::class, $repository);

        $command = new RetryBatchCommand;
        $command->setLaravel($container);

        $exitCode = $command->run(new ArrayInput(['id' => ['missing-batch-id']]), $output = new BufferedOutput);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Unable to find a batch with ID [missing-batch-id].', $output->fetch());
    }

    public function testItFailsWhenTheBatchHasNoFailedJobs()
    {
        $container = new Application;
        $repository = Double::for(BatchRepository::class);
        $repository->expects('find')->with('batch-id')->returns(new class
        {
            public $failedJobIds = [];
        });
        $container->instance(BatchRepository::class, $repository);

        $command = new RetryBatchCommand;
        $command->setLaravel($container);

        $exitCode = $command->run(new ArrayInput(['id' => ['batch-id']]), $output = new BufferedOutput);

        $this->assertSame(Command::FAILURE, $exitCode);

        $output = $output->fetch();

        $this->assertStringContainsString('The batch with ID [batch-id] does not contain any failed jobs.', $output);
        $this->assertStringNotContainsString('Pushing failed queue jobs of the batch', $output);
    }

    public function testItCanBeRunInIsolation()
    {
        $container = new Application;
        $repository = Double::for(BatchRepository::class);
        $repository->expects('find')->with('batch-id')->returns(null);
        $container->instance(BatchRepository::class, $repository);

        $mutex = Double::for(CommandMutex::class);
        $mutex->expects('create')->returns(true);
        $mutex->expects('forget')->returns(true);
        $container->instance(CommandMutex::class, $mutex);

        $command = new RetryBatchCommand;
        $command->setLaravel($container);

        $exitCode = $command->run(
            new ArrayInput(['id' => ['batch-id'], '--isolated' => true]), new BufferedOutput
        );

        $this->assertSame(Command::FAILURE, $exitCode);
    }
}
