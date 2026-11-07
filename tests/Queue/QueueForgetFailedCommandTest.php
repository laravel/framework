<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Console\Command;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\ForgetFailedCommand;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class QueueForgetFailedCommandTest extends TestCase
{
    use VerifiesDoubles;

    public function testItForgetsASingleJob()
    {
        $container = new Application;
        $failer = Double::for(FailedJobProviderInterface::class);
        $failer->expects('forget')->with('5')->returns(true);
        $container->instance('queue.failer', $failer->instance());

        $command = new ForgetFailedCommand;
        $command->setLaravel($container);

        $exitCode = $command->run(new ArrayInput(['id' => ['5']]), $output = new BufferedOutput);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Failed job [5] deleted successfully.', $output->fetch());
    }

    public function testItForgetsMultipleJobs()
    {
        $container = new Application;
        $failer = Double::for(FailedJobProviderInterface::class);
        $failer->expects('forget')->with('5')->returns(true);
        $failer->expects('forget')->with('6')->returns(true);
        $container->instance('queue.failer', $failer->instance());

        $command = new ForgetFailedCommand;
        $command->setLaravel($container);

        $exitCode = $command->run(new ArrayInput(['id' => ['5', '6']]), $output = new BufferedOutput);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $output = $output->fetch();

        $this->assertStringContainsString('Failed job [5] deleted successfully.', $output);
        $this->assertStringContainsString('Failed job [6] deleted successfully.', $output);
    }

    public function testItFailsWhenAJobCannotBeFound()
    {
        $container = new Application;
        $failer = Double::for(FailedJobProviderInterface::class);
        $failer->expects('forget')->with('5')->returns(false);
        $container->instance('queue.failer', $failer->instance());

        $command = new ForgetFailedCommand;
        $command->setLaravel($container);

        $exitCode = $command->run(new ArrayInput(['id' => ['5']]), $output = new BufferedOutput);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('No failed job matches the ID [5].', $output->fetch());
    }

    public function testItForgetsTheRemainingJobsWhenOneCannotBeFound()
    {
        $container = new Application;
        $failer = Double::for(FailedJobProviderInterface::class);
        $failer->expects('forget')->with('5')->returns(true);
        $failer->expects('forget')->with('6')->returns(false);
        $failer->expects('forget')->with('7')->returns(true);
        $container->instance('queue.failer', $failer->instance());

        $command = new ForgetFailedCommand;
        $command->setLaravel($container);

        $exitCode = $command->run(new ArrayInput(['id' => ['5', '6', '7']]), $output = new BufferedOutput);

        $this->assertSame(Command::FAILURE, $exitCode);

        $output = $output->fetch();

        $this->assertStringContainsString('Failed job [5] deleted successfully.', $output);
        $this->assertStringContainsString('No failed job matches the ID [6].', $output);
        $this->assertStringContainsString('Failed job [7] deleted successfully.', $output);
    }
}
