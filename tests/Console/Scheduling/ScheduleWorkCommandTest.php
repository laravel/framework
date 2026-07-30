<?php

namespace Illuminate\Tests\Console\Scheduling;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\Scheduling\ScheduleWorkCommand;
use Illuminate\Console\Signals;
use Illuminate\Support\Carbon;
use Illuminate\Tests\Console\Fixtures\FakeSignalsRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;

class ScheduleWorkCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * The signal availability resolver in place before the test ran.
     *
     * @var (callable(): bool)|null
     */
    protected $originalAvailabilityResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalAvailabilityResolver = (new ReflectionProperty(Signals::class, 'availabilityResolver'))
            ->getValue();

        Carbon::setTestNow(Carbon::create(2026, 6, 27, 12, 0, 30));
    }

    protected function tearDown(): void
    {
        Signals::resolveAvailabilityUsing($this->originalAvailabilityResolver);

        parent::tearDown();
    }

    public function test_stop_signal_marks_the_worker_to_quit()
    {
        if (! extension_loaded('pcntl')) {
            $this->markTestSkipped('The pcntl extension is required to trap signals.');
        }

        // When a handler is registered, Signals chains any handler already bound
        // to the signal (see Signals::initializeSignal). Other tests may leave
        // real handlers in place, so reset these signals to their default
        // disposition to keep this test isolated from the global signal state.
        $previousHandlers = [];

        foreach ([SIGINT, SIGTERM, SIGQUIT] as $signal) {
            $previousHandlers[$signal] = pcntl_signal_get_handler($signal);

            pcntl_signal($signal, SIG_DFL);
        }

        try {
            Signals::resolveAvailabilityUsing(fn () => true);

            $registry = new FakeSignalsRegistry;

            $command = new ScheduleWorkCommandTestStub;

            // Wire the command up to a fake signal registry so we can simulate a
            // signal being delivered without sending a real one to the process.
            (fn () => $this->signals = new Signals($registry))->call($command);

            $command->callListenForSignals();

            $this->assertFalse($command->shouldQuit(), 'The worker should not quit before a signal is received.');

            $registry->handle(SIGTERM);

            $this->assertTrue($command->shouldQuit(), 'The worker should be marked to quit after a SIGTERM.');
        } finally {
            foreach ($previousHandlers as $signal => $handler) {
                pcntl_signal($signal, $handler ?: SIG_DFL);
            }
        }
    }

    public function test_in_flight_executions_finish_before_the_worker_quits()
    {
        $execution = m::mock(Process::class);
        $execution->shouldReceive('getIncrementalOutput')->andReturn('scheduled task ran', '');
        $execution->shouldReceive('getIncrementalErrorOutput')->andReturn('');

        // The worker should poll the running execution after the signal arrives,
        // wait for it to report finished, and flush its output before quitting.
        $execution->shouldReceive('isRunning')->twice()->andReturn(true, false);

        $command = new ScheduleWorkCommandTestStub;
        $command->setOutput(new OutputStyle(new ArrayInput([]), $buffer = new BufferedOutput));

        // Simulate the stop signal arriving while a "schedule:run" execution is
        // still in progress (after the worker's very first loop tick).
        $command->onTick = function (ScheduleWorkCommandTestStub $command) {
            if ($command->ticks === 1) {
                $command->markShouldQuit();
            }
        };

        $status = $command->work('schedule:run', [$execution]);

        $this->assertSame(ScheduleWorkCommand::SUCCESS, $status);
        $this->assertSame([], $command->remainingExecutions(), 'The execution should have been drained before quitting.');
        $this->assertStringContainsString('scheduled task ran', $buffer->fetch());
    }

    public function test_no_new_executions_are_started_after_a_stop_signal()
    {
        // A new run would normally be started on the zero second of the minute.
        Carbon::setTestNow(Carbon::create(2026, 6, 27, 12, 0, 0));

        $command = new ScheduleWorkCommandTestStub;
        $command->markShouldQuit();

        // With no executions in flight and a stop signal already received, the
        // worker must exit immediately without starting a new "schedule:run".
        // If the guard regressed, it would try to spawn a real process here.
        $status = $command->work('schedule:run');

        $this->assertSame(ScheduleWorkCommand::SUCCESS, $status);
        $this->assertSame([], $command->remainingExecutions());
        $this->assertSame(1, $command->ticks, 'The worker should exit after a single tick when idle and quitting.');
    }
}

class ScheduleWorkCommandTestStub extends ScheduleWorkCommand
{
    /**
     * The number of times the worker has ticked (slept).
     *
     * @var int
     */
    public $ticks = 0;

    /**
     * A callback invoked on every worker tick, used to drive the loop in tests.
     *
     * @var (callable(self): void)|null
     */
    public $onTick;

    public function callListenForSignals(): void
    {
        $this->listenForSignals();
    }

    public function shouldQuit(): bool
    {
        return $this->shouldQuit;
    }

    public function markShouldQuit(): void
    {
        $this->shouldQuit = true;
    }

    public function work($command, array $executions = [])
    {
        $this->executions = $executions;

        return parent::work($command);
    }

    public function remainingExecutions(): array
    {
        return $this->executions;
    }

    protected function sleep()
    {
        $this->ticks++;

        if ($this->onTick) {
            ($this->onTick)($this);
        }
    }
}
