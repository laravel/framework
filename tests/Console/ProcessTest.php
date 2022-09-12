<?php

namespace Illuminate\Tests;

use Illuminate\Console\Exceptions\ProcessAlreadyStarted;
use Illuminate\Console\Exceptions\ProcessFailedException;
use Illuminate\Console\Exceptions\ProcessInPoolNotStartedException;
use Illuminate\Console\Exceptions\ProcessNotStartedException;
use Illuminate\Console\Process;
use Illuminate\Console\Process\Factory;
use Illuminate\Console\Process\Pool;
use Illuminate\Console\Process\Results\FakeResult;
use Illuminate\Console\Process\Results\Result;
use Mockery as m;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    /**
     * @var \Illuminate\Console\Process\Factory
     */
    protected $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Factory;
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testOk()
    {
        $this->factory->fake();

        $result = $this->factory->run($this->ls());

        $this->assertTrue($result->ok());
    }

    public function testFailed()
    {
        $this->factory->fake($this->factory::result('my-process-output', 1));

        $result = $this->factory->run($this->ls());

        $this->assertTrue($result->failed());
    }

    public function testCommandGetsEscaped()
    {
        $this->factory->fake();

        $result = $this->factory->run(['ls', '-la']);

        $this->factory->assertRan(function ($process) {
            return windows_os() ? $process->command() === 'ls -la' : $process->command() === "'ls' '-la'";
        });
    }

    public function testOutput()
    {
        $this->factory->fake(function ($process) {
            if (str($process->getCommandLine())->contains('fooo')) {
                return $this->factory::result('some failure', 1);
            }
        });

        $this->factory->fake([
            'nuno' => $this->factory::result('drwxr-xr-x   25 nunomaduro'),
            'taylor*' => $this->factory::result('drwxr-xr-x   25 taylorotwell'),
            '*joe*' => $this->factory::result('drwxr-xr-x   25 joe'),
            '*' => $this->factory::result('drwxr-xr-x   25 root'),
        ]);

        $result = $this->factory->run('fooo');
        $this->assertSame('some failure', $result->output());
        $this->assertTrue($result->failed());

        $result = $this->factory->run('nuno');
        $this->assertSame('drwxr-xr-x   25 nunomaduro', $result->output());

        $result = $this->factory->run('wwwjoewww');
        $this->assertSame('drwxr-xr-x   25 joe', $result->output());

        $result = $this->factory->run('taylor otwell');
        $this->assertSame('drwxr-xr-x   25 taylorotwell', $result->output());

        $result = $this->factory->run($this->ls());
        $this->assertSame('drwxr-xr-x   25 root', $result->output());
    }

    public function testOutputAsCallback()
    {
        $this->factory->fake(function ($process) {
            return $this->factory::result('my stdout output');
        });

        $outputViaCallback = '';
        $typeViaCallback = null;

        $output = $this->factory->run('ls', function ($output, $type) use (&$outputViaCallback, &$typeViaCallback) {
            $typeViaCallback = $type;
            $outputViaCallback = $output;
        })->output();

        $this->assertSame($outputViaCallback, $output);
        $this->assertSame('my stdout output', $output);
        $this->assertSame(Process::STDOUT, $typeViaCallback);
    }

    public function testResultErrorOutput()
    {
        $this->factory->fake(function () {
            return $this->factory::result('my stdout output', 1, 'my stderr output');
        });

        $result = $this->factory->run('');
        $this->assertSame('my stdout output', $result->output());
        $this->assertTrue($result->failed());
        $this->assertSame('my stderr output', $result->errorOutput());
    }

    public function testErrorOutputAsCallback()
    {
        $this->factory->fake(function ($process) {
            return $this->factory::result('', 1, ['my stderr line 1', 'my stderr line 2']);
        });

        $outputViaCallback = '';
        $typeViaCallback = null;

        $output = $this->factory->output(function ($output, $type) use (&$outputViaCallback, &$typeViaCallback) {
            $typeViaCallback = $type;
            $outputViaCallback = $output;
        })->run('ls')->errorOutput();

        $this->assertSame($outputViaCallback, $output);
        $this->assertSame("my stderr line 1\nmy stderr line 2\n", $outputViaCallback);
        $this->assertSame(Process::STDERR, $typeViaCallback);
    }

    public function testOutputWhenFakeDoesNotExist()
    {
        $this->factory->fake([
            'nuno' => $this->factory::result('drwxr-xr-x   25 nunomaduro'),
        ]);

        $result = $this->factory->run('nuno');
        $this->assertSame('drwxr-xr-x   25 nunomaduro', $result->output());

        $result = $this->factory->path(__DIR__)->run($this->ls());
        $this->assertStringContainsString('ProcessTest', $result->output());
        $this->assertTrue($result->ok());

        $this->factory->fake();

        $result = $this->factory->run('nuno');
        $this->assertSame('drwxr-xr-x   25 nunomaduro', $result->output());

        $result = $this->factory->path(__DIR__)->run($this->ls());
        $this->assertStringContainsString('', $result->output());
        $this->assertTrue($result->ok());
    }

    public function testResultExceptionIsThrownIfTheProcessFails()
    {
        $this->factory->fake($this->factory->result('', 1, ['Hello World']));

        $exception = null;
        $exceptionViaCallback = null;

        try {
            $this->factory->run('echo "Hello World" >&2; exit 1;')->throw(function ($e) use (&$exceptionViaCallback) {
                $exceptionViaCallback = $e;
            });
        } catch (ProcessFailedException $exception) {
            // ..
        }

        $this->assertInstanceOf(ProcessFailedException::class, $exception);
        $this->assertSame($exception, $exceptionViaCallback);

        $this->assertSame('', $exception->result()->output());
        $this->assertSame("Hello World\n", $exception->result()->errorOutput());
        $this->assertSame(1, $exception->result()->exitCode());
        $this->assertStringContainsString('Hello World', $exception->process()->getCommandLine());
        $this->assertNull($exception->getPrevious());
    }

    public function testResultExceptionIsThrownIfTheProcessFailsAndTheGivenConditionIsTrue()
    {
        $this->factory->fake($this->factory->result('', 1, ['Hello World']));

        $exception = null;
        $exceptionViaCallback = null;

        try {
            $this->factory->run('echo "Hello World" >&2; exit 1;')->throwIf(true, function ($e) use (&$exceptionViaCallback) {
                $exceptionViaCallback = $e;
            });
        } catch (ProcessFailedException $exception) {
            // ..
        }

        $this->assertInstanceOf(ProcessFailedException::class, $exception);
        $this->assertSame($exception, $exceptionViaCallback);

        $this->factory->fake($this->factory->result('', 1, ['Hello World']));

        $exception = null;
        $exceptionViaCallback = null;

        try {
            $this->factory->run('echo "Hello World" >&2; exit 1;')->throwIf(false, function ($e) use (&$exceptionViaCallback) {
                $exceptionViaCallback = $e;
            });
        } catch (ProcessFailedException $exception) {
            // ..
        }

        $this->assertNull($exception);
        $this->assertNull($exceptionViaCallback);
    }

    public function testResultExceptionIsThrownIfTheProcessFailsAndTheGivenConditionIsFalse()
    {
        $this->factory->fake($this->factory->result('', 1, ['Hello World']));

        $exception = null;
        $exceptionViaCallback = null;

        try {
            $this->factory->run('echo "Hello World" >&2; exit 1;')->throwUnless(false, function ($e) use (&$exceptionViaCallback) {
                $exceptionViaCallback = $e;
            });
        } catch (ProcessFailedException $exception) {
            // ..
        }

        $this->assertInstanceOf(ProcessFailedException::class, $exception);
        $this->assertSame($exception, $exceptionViaCallback);

        $this->factory->fake($this->factory->result('', 1, ['Hello World']));

        $exception = null;
        $exceptionViaCallback = null;

        try {
            $this->factory->run('echo "Hello World" >&2; exit 1;')->throwUnless(true, function ($e) use (&$exceptionViaCallback) {
                $exceptionViaCallback = $e;
            });
        } catch (ProcessFailedException $exception) {
            // ..
        }

        $this->assertNull($exception);
        $this->assertNull($exceptionViaCallback);
    }

    public function testResultEnsuresTheProcessStarts()
    {
        $this->expectException(ProcessNotStartedException::class);

        if (windows_os()) {
            $this->expectExceptionMessage("The process \"{$this->ls()}\" failed to start.");
        } else {
            $this->expectExceptionMessage("The process \"'{$this->ls()}'\" failed to start.");
        }

        new Result(new Process([$this->ls()]), []);
    }

    public function testFakeResultEnsuresTheProcessStarts()
    {
        $this->expectException(ProcessNotStartedException::class);
        $this->expectExceptionMessage('The process failed to start.');

        tap(new FakeResult('foo', 0, ''))->wait();
    }

    public function testFakes()
    {
        $this->factory->fake();
        $this->assertSame('', $this->factory->run()->output());
        $this->assertSame('', $this->factory->run()->output());
        $this->factory->assertRanCount(2);
        (fn () => $this->stubs = [])->call($this->factory);
        (fn () => $this->recorded = [])->call($this->factory);

        $this->factory->fake($this->factory::result('output 1'));
        $this->assertSame('output 1', $this->factory->run()->output());
        $this->assertSame('output 1', $this->factory->run()->output());
        $this->factory->assertRanCount(2);
        (fn () => $this->stubs = [])->call($this->factory);
        (fn () => $this->recorded = [])->call($this->factory);

        $this->factory->fake(fn () => $this->factory::result('output 1'));
        $this->assertSame('output 1', $this->factory->run()->output());
        $this->assertSame('output 1', $this->factory->run()->output());
        $this->factory->assertRanCount(2);
        (fn () => $this->stubs = [])->call($this->factory);
        (fn () => $this->recorded = [])->call($this->factory);

        $this->factory->fake(fn () => null);
        $this->assertStringContainsString('ProcessTest', $this->factory->path(__DIR__)->run($this->ls())->output());
        $this->assertStringContainsString('ProcessTest', $this->factory->path(__DIR__)->run($this->ls())->output());
        $this->factory->assertRanCount(2);
        (fn () => $this->stubs = [])->call($this->factory);
        (fn () => $this->recorded = [])->call($this->factory);

        $this->factory->fake(['' => $this->factory::result('output 1')]);
        $this->assertSame('output 1', $this->factory->run()->output());
        $this->assertSame('output 1', $this->factory->run()->output());
        $this->factory->assertRanCount(2);
        (fn () => $this->stubs = [])->call($this->factory);
        (fn () => $this->recorded = [])->call($this->factory);

        $this->factory->fake(['' => fn () => $this->factory::result('output 1')]);
        $this->assertSame('output 1', $this->factory->run()->output());
        $this->assertSame('output 1', $this->factory->run()->output());
        $this->factory->assertRanCount(2);
        (fn () => $this->stubs = [])->call($this->factory);
        (fn () => $this->recorded = [])->call($this->factory);

        $this->factory->fake(['' => fn () => null]);
        $this->assertStringContainsString('ProcessTest', $this->factory->path(__DIR__)->run($this->ls())->output());
        $this->assertStringContainsString('ProcessTest', $this->factory->path(__DIR__)->run($this->ls())->output());
        $this->factory->assertRanCount(2);
        (fn () => $this->stubs = [])->call($this->factory);
        (fn () => $this->recorded = [])->call($this->factory);
    }

    public function testWait()
    {
        $result = $this->factory->path(__DIR__)->run($this->ls());
        $this->assertStringContainsString('ProcessTest', $result->wait()->output());
        $this->assertTrue($result->ok());

        $this->factory->fake();

        $result = $this->factory->path(__DIR__)->run($this->ls());
        $this->assertStringContainsString('', $result->wait()->output());
        $this->assertTrue($result->ok());
    }

    public function testWaitDoesNotWaitTwice()
    {
        $called = 0;
        $result = new Result(tap(new Process([$this->ls()]))->start(), [function () use (&$called) {
            $called++;
        }]);

        $result->wait();
        $result->wait();
        $result->wait();

        $this->assertSame(1, $called);

        $called = 0;
        $result = new FakeResult('', 0, '');
        $result->start(new Process([$this->ls()]), null, [function () use (&$called) {
            $called++;
        }]);
        $result->wait();
        $result->wait();
        $result->wait();

        $this->assertSame(1, $called);
    }

    public function testDoesNotThrowOnOkProcesses()
    {
        $result = $this->factory->path(__DIR__)->run($this->ls())->throw();
        $this->assertStringContainsString('ProcessTest', $result->wait()->output());
        $this->assertTrue($result->ok());

        $this->factory->fake();

        $result = $this->factory->path(__DIR__)->run($this->ls())->throw();
        $this->assertStringContainsString('', $result->wait()->output());
        $this->assertTrue($result->ok());
    }

    public function testTimeout()
    {
        $this->factory->fake();

        $result = $this->factory->run('sleep 2');
        $this->assertSame(60.0, $result->process()->getTimeout());

        $result = $this->factory->forever()->run('sleep 2');
        $this->assertSame(null, $result->process()->getTimeout());

        $result = $this->factory->timeout(1)->run('sleep 2');
        $this->assertSame(1.0, $result->process()->getTimeout());

        $result = $this->factory->timeout(500)->run('sleep 2');
        $this->assertSame(500.0, $result->process()->getTimeout());
    }

    public function testSignals()
    {
        if (! extension_loaded('pcntl')) {
            $this->markTestSkipped('Test requires pcntl extension.');
        }

        $result = $this->factory->path(__DIR__)->async()->run($this->ls());
        $result->process()->signal(SIGKILL);

        $this->assertTrue($result->failed());
        $this->assertSame(128 + SIGKILL, $result->exitCode());
    }

    public function testPath()
    {
        $this->factory->fake();

        $result = $this->factory->path(__DIR__)->run($this->ls());

        $this->assertSame(__DIR__, $result->process()->getWorkingDirectory());
    }

    public function testForever()
    {
        $this->factory->fake();

        $result = $this->factory->forever()->run($this->ls());

        $this->assertNull($result->process()->getTimeout());
    }

    public function testRunning()
    {
        $result = $this->factory->run($this->ls());
        $this->assertFalse($result->running());
        $this->assertTrue($result->ok());
        $this->assertFalse($result->running());

        $this->factory->fake();

        $result = $this->factory->run($this->ls());
        $this->assertFalse($result->running());
        $this->assertTrue($result->ok());
        $this->assertFalse($result->running());
    }

    public function testAsyncRunning()
    {
        $result = $this->factory->async()->run($this->ls());
        $this->assertTrue($result->running());
        $this->assertTrue($result->ok());
        $this->assertFalse($result->running());

        $this->factory->fake();

        $result = $this->factory->async()->run($this->ls());
        $this->assertTrue($result->running());
        $this->assertTrue($result->ok());
        $this->assertFalse($result->running());
    }

    public function testToString()
    {
        $result = $this->factory->path(__DIR__)->run($this->ls());
        $this->assertStringContainsString('ProcessTest', (string) $result);
        $this->assertStringContainsString('ProcessTest', $result->toString());
        $this->assertTrue($result->ok());

        $this->factory->fake($this->factory::result('ProcessOutput'));

        $result = $this->factory->path(__DIR__)->run($this->ls());
        $this->assertStringContainsString('ProcessOutput', (string) $result);
        $this->assertStringContainsString('ProcessOutput', $result->toString());
        $this->assertTrue($result->ok());
    }

    public function testResultToArray()
    {
        $this->factory->fake([
            'one' => $this->factory::result(['My line 1']),
            'two' => $this->factory::result(['My line 1', 'My line 2']),
            'three' => $this->factory::result("My line 1\nMy line 2\nMy line 3\n"),
        ]);

        $result = $this->factory->run('one');
        $this->assertCount(1, $result->toArray());
        $this->assertSame('My line 1', $result->toArray()[0]);

        $result = $this->factory->run('two');
        $this->assertCount(2, $result->toArray());
        $this->assertSame('My line 1', $result->toArray()[0]);
        $this->assertSame('My line 2', $result->toArray()[1]);

        $result = $this->factory->run('three');
        $this->assertCount(3, $result->toArray());
        $this->assertSame('My line 1', $result->toArray()[0]);
        $this->assertSame('My line 2', $result->toArray()[1]);
        $this->assertSame('My line 3', $result->toArray()[2]);
    }

    public function testResultArrayAccess()
    {
        $this->factory->fake(fn () => $this->factory::result(['My line 1', 'My line 2']));

        $result = $this->factory->run('two');

        $this->assertCount(2, $result->toArray());
        $this->assertSame('My line 1', $result[0]);
        $this->assertSame('My line 2', $result[1]);
    }

    public function testResultAsIterator()
    {
        $this->factory->fake(fn () => $this->factory::result(['My line 1', 'My line 2']));

        $result = $this->factory->run('two');

        $output = iterator_to_array($result);

        $this->assertCount(2, $output);
        $this->assertSame('My line 1', $output[0]);
        $this->assertSame('My line 2', $output[1]);
    }

    public function testCommand()
    {
        $result = $this->factory->path(__DIR__)->command($this->ls())->run();
        $this->assertStringContainsString('ProcessTest', (string) $result);
        $this->assertStringContainsString('ProcessTest', $result->toString());
        $this->assertTrue($result->ok());

        $this->factory->fake([
            $this->ls() => $this->factory::result('ProcessOutput'),
        ]);

        $result = $this->factory->path(__DIR__)->command($this->ls())->run();
        $this->assertStringContainsString('ProcessOutput', (string) $result);
        $this->assertStringContainsString('ProcessOutput', $result->toString());
        $this->assertTrue($result->ok());
    }

    public function testPendingProcessMayNotStartTwice()
    {
        $this->expectException(ProcessAlreadyStarted::class);
        $this->expectExceptionMessage('The process has already been started.');

        $this->factory->fake();

        $pending = $this->factory->async(false);

        $pending->run();
        $pending->run();
    }

    public function testProcessesOnPoolMustCallRun()
    {
        $this->factory->fake();

        $this->expectException(ProcessInPoolNotStartedException::class);

        $this->factory->pool(function (Pool $pool) {
            return [$pool->path(__DIR__)];
        });
    }

    public function testPoolResults()
    {
        $this->factory->fake([
            'one' => $this->factory::result(['My line 1']),
            'two' => $this->factory::result(['My line 1', 'My line 2'], 1),
            'three' => $this->factory::result(['My line 1', 'My line 2', 'My line 3'], 143),
        ]);

        $results = $this->factory->pool(fn (Pool $pool) => [
            $pool->run('one'),
            $pool->run('two'),
            $pool->run('three'),
        ]);

        $this->assertCount(3, $results);

        $this->assertTrue($results[0]->ok());
        $this->assertSame(['My line 1'], $results[0]->toArray());

        $this->assertTrue($results[1]->failed());
        $this->assertSame(1, $results[1]->exitCode());
        $this->assertSame(['My line 1', 'My line 2'], $results[1]->toArray());

        $this->assertTrue($results[2]->failed());
        $this->assertSame(143, $results[2]->exitCode());
        $this->assertSame(['My line 1', 'My line 2', 'My line 3'], $results[2]->toArray());
    }

    public function testPoolResultsMayBeAsserted()
    {
        $this->factory->fake([
            'one' => $this->factory::result(['My line 1']),
            'two' => $this->factory::result(['My line 1', 'My line 2'], 1),
            'three' => $this->factory::result(['My line 1', 'My line 2', 'My line 3'], 143),
        ]);

        $this->factory->pool(fn (Pool $pool) => [
            $pool->run('one'),
            $pool->run('two'),
            $pool->run('three'),
        ]);

        $this->factory->assertRanInOrder([
            'one',
            'two',
            'three',
        ]);

        $this->factory->assertRanInOrder([
            fn ($process, $response) => $process->command() === 'one' && $response->ok(),
            fn ($process, $response) => $process->command() === 'two' && $response->failed(),
            fn ($process, $response) => $process->command() === 'three' && $response->failed(),
        ]);
    }

    public function testProcessGetters()
    {
        $this->factory->fake();

        $result = $this->factory->path(__DIR__)
            ->timeout(45.0)
            ->run($this->ls());

        $process = $result->process();

        $this->assertSame($this->ls(), $process->command());
        $this->assertSame(45.0, $process->timeout());
        $this->assertSame(__DIR__, $process->path());
    }

    public function testAssertRan()
    {
        $this->factory->fake();

        $this->factory->run('ls');

        $this->factory->assertRan(function ($process) {
            return $process->command() === 'ls';
        });

        $factory = $this->factory->assertRan('ls');

        $this->assertSame($this->factory, $factory);
    }

    public function testAssertRanWithArray()
    {
        $this->factory->fake();

        $this->factory->run(['curl', 'https://google.com']);

        $this->factory->assertRan(['curl', 'https://google.com']);

        if (windows_os()) {
            $this->factory->assertRan('curl "https://google.com"');
        } else {
            $this->factory->assertRan("'curl' 'https://google.com'");
        }
    }

    public function testAssertRanMayFail()
    {
        $this->expectException(AssertionFailedError::class);

        $this->factory->fake();

        $this->factory->run('ls');

        $this->factory->assertRan('nop');
    }

    public function testAssertRanInOrder()
    {
        $this->factory->fake();

        $this->factory->run('ls -l0');
        $this->factory->run('ls -l1');
        $this->factory->run('ls -l2');

        $this->factory->assertRanInOrder([
            fn ($process) => $process->command() === 'ls -l0',
            fn ($process) => $process->command() === 'ls -l1',
            fn ($process) => $process->command() === 'ls -l2',
        ]);

        $factory = $this->factory->assertRanInOrder(['ls -l0', 'ls -l1', 'ls -l2']);

        $this->assertSame($this->factory, $factory);
    }

    public function testAssertRanInOrderWithArray()
    {
        $this->factory->fake();

        $this->factory->run(['ls', '-l0']);
        $this->factory->run(['ls', '-l1']);
        $this->factory->run(['ls', '-l2']);

        $this->factory->assertRanInOrder([
            ['ls', '-l0'],
            ['ls', '-l1'],
            ['ls', '-l2'],
        ]);
    }

    public function testAssertRanInOrderMayFail()
    {
        $this->expectException(AssertionFailedError::class);

        $this->factory->fake();

        $this->factory->run('ls -l0');
        $this->factory->run('ls -l1');
        $this->factory->run('ls -l2');

        $this->factory->assertRanInOrder(['ls -l0', 'ls -l2', 'ls -l1']);
    }

    public function testAssertNotRan()
    {
        $this->factory->fake();

        $this->factory->run('ls');

        $this->factory->assertNotRan(function ($process) {
            return $process->command() === 'sleep';
        });

        $factory = $this->factory->assertNotRan('sleep');

        $this->assertSame($this->factory, $factory);
    }

    public function testAssertNotRanMayFail()
    {
        $this->expectException(AssertionFailedError::class);

        $this->factory->fake();

        $this->factory->run('ls');

        $this->factory->assertNotRan('ls');
    }

    public function testAssertNothingRan()
    {
        $this->factory->fake();

        $factory = $this->factory->assertNothingRan();

        $this->assertSame($this->factory, $factory);
    }

    public function testAssertNothingRanMayFail()
    {
        $this->expectException(AssertionFailedError::class);

        $this->factory->fake();

        $this->factory->run('ls');

        $this->factory->assertNothingRan();
    }

    public function testAssertRanCount()
    {
        $this->factory->fake();

        $this->factory->assertRanCount(0);

        $this->factory->run('ls');

        $factory = $this->factory->assertRanCount(1);

        $this->assertSame($this->factory, $factory);
    }

    public function testAssertRanCountMayFail()
    {
        $this->expectException(AssertionFailedError::class);

        $this->factory->fake();

        $this->factory->run('ls');

        $this->factory->assertRanCount(3);
    }

    protected function ls()
    {
        return windows_os() ? 'dir' : 'ls';
    }
}
