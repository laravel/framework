<?php

namespace Illuminate\Tests\Process;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Process\Factory;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ProcessTest extends TestCase
{
    public function testSuccessfulProcess()
    {
        $factory = new Factory;
        $result = $factory->path(__DIR__)->run($this->ls());

        $this->assertInstanceOf(ProcessResult::class, $result);
        $this->assertTrue($result->successful());
        $this->assertFalse($result->failed());
        $this->assertEquals(0, $result->exitCode());
        $this->assertTrue(str_contains($result->output(), 'ProcessTest.php'));
        $this->assertEquals('', $result->errorOutput());

        $result->throw();
        $result->throwIf(true);
    }

    public function testProcessPool()
    {
        $factory = new Factory;

        $pool = $factory->pool(function ($pool) {
            return [
                $pool->path(__DIR__)->command($this->ls()),
                $pool->path(__DIR__)->command($this->ls()),
            ];
        });

        $results = $pool->start()->wait();

        $this->assertTrue($results[0]->successful());
        $this->assertTrue($results[1]->successful());

        $this->assertTrue(str_contains($results[0]->output(), 'ProcessTest.php'));
        $this->assertTrue(str_contains($results[1]->output(), 'ProcessTest.php'));

        $this->assertTrue($results->successful());
    }

    public function testProcessPoolFailed()
    {
        $factory = new Factory;

        $factory->fake([
            'cat *' => $factory->result(exitCode: 1),
        ]);

        $pool = $factory->pool(function ($pool) {
            return [
                $pool->path(__DIR__)->command($this->ls()),
                $pool->path(__DIR__)->command('cat test'),
            ];
        });

        $results = $pool->start()->wait();

        $this->assertTrue($results[0]->successful());
        $this->assertTrue($results[1]->failed());

        $this->assertTrue($results->failed());
    }

    public function testInvokedProcessPoolCount()
    {
        $factory = new Factory;

        $pool = $factory->pool(function ($pool) {
            return [
                $pool->path(__DIR__)->command($this->ls()),
                $pool->path(__DIR__)->command($this->ls()),
            ];
        })->start();

        $this->assertCount(2, $pool);
    }

    public function testProcessPoolCanReceiveOutputForEachProcessViaStartMethod()
    {
        $factory = new Factory;

        $output = [];

        $pool = $factory->pool(function ($pool) {
            return [
                $pool->path(__DIR__)->command($this->ls()),
                $pool->path(__DIR__)->command($this->ls()),
            ];
        })->start(function ($type, $buffer, $key) use (&$output) {
            $output[$key][$type][] = $buffer;
        });

        $poolResults = $pool->wait();

        $this->assertTrue(count($output[0]['out']) > 0);
        $this->assertTrue(count($output[1]['out']) > 0);
        $this->assertInstanceOf(ProcessResult::class, $poolResults[0]);
        $this->assertInstanceOf(ProcessResult::class, $poolResults[1]);
        $this->assertTrue(str_contains($poolResults[0]->output(), 'ProcessTest.php'));
        $this->assertTrue(str_contains($poolResults[1]->output(), 'ProcessTest.php'));
    }

    public function testProcessPoolResultsCanBeEvaluatedByName()
    {
        $factory = new Factory;

        $pool = $factory->pool(function ($pool) {
            return [
                $pool->as('first')->path(__DIR__)->command($this->ls()),
                $pool->as('second')->path(__DIR__)->command($this->ls()),
            ];
        })->wait();

        $this->assertTrue($pool['first']->successful());
        $this->assertTrue($pool['second']->successful());

        $this->assertTrue(str_contains($pool['first']->output(), 'ProcessTest.php'));
        $this->assertTrue(str_contains($pool['second']->output(), 'ProcessTest.php'));
    }

    public function testOutputCanBeRetrievedViaStartCallback()
    {
        $factory = new Factory;

        $output = [];

        $process = $factory->path(__DIR__)->start($this->ls(), function ($type, $buffer) use (&$output) {
            $output[] = $buffer;
        });

        $process->wait();

        $this->assertTrue(str_contains(implode('', $output), 'ProcessTest.php'));
    }

    public function testOutputCanBeRetrievedViaWaitCallback()
    {
        $factory = new Factory;

        $output = [];

        $process = $factory->path(__DIR__)->start($this->ls());

        $process->wait(function ($type, $buffer) use (&$output) {
            $output[] = $buffer;
        });

        $this->assertTrue(str_contains(implode('', $output), 'ProcessTest.php'));
    }

    public function testBasicProcessFake()
    {
        $factory = new Factory;
        $factory->fake();

        $result = $factory->run('ls -la');

        $this->assertEquals('', $result->output());
        $this->assertEquals('', $result->errorOutput());
        $this->assertEquals(0, $result->exitCode());
        $this->assertTrue($result->successful());
    }

    public function testBasicProcessFakeWithMultiLineCommand()
    {
        $factory = new Factory;

        $factory->preventStrayProcesses();

        $factory->fake([
            '*' => $expectedOutput = 'The output',
        ]);

        $result = $factory->run(<<<'COMMAND'
        git clone --depth 1 \
              --single-branch \
              --branch main \
              git://some-url .
        COMMAND);

        $this->assertSame(0, $result->exitCode());
        $this->assertSame("$expectedOutput\n", $result->output());
    }

    public function testProcessFakeWithMultiLineCommand()
    {
        $factory = new Factory;

        $factory->preventStrayProcesses();

        $factory->fake([
            '*--branch main*' => 'not this one',
            '*--branch develop*' => $expectedOutput = 'yes thank you',
        ]);

        $result = $factory->run(<<<'COMMAND'
        git clone --depth 1 \
              --single-branch \
              --branch develop \
              git://some-url .
        COMMAND);

        $this->assertSame(0, $result->exitCode());
        $this->assertSame("$expectedOutput\n", $result->output());
    }

    public function testProcessFakeExitCodes()
    {
        $factory = new Factory;
        $factory->fake(fn () => $factory->result('test output', exitCode: 1));

        $result = $factory->run('ls -la');
        $this->assertFalse($result->successful());
    }

    public function testProcessFakeExitCodeShorthand()
    {
        $factory = new Factory;
        $factory->fake(['ls -la' => 1]);

        $result = $factory->run('ls -la');
        $this->assertSame(1, $result->exitCode());
        $this->assertFalse($result->successful());
    }

    public function testBasicProcessFakeWithCustomOutput()
    {
        $factory = new Factory;
        $factory->fake(fn () => $factory->result('test output'));

        $result = $factory->run('ls -la');
        $this->assertEquals("test output\n", $result->output());

        // Array of output...
        $factory = new Factory;
        $factory->fake(fn () => $factory->result(['line 1', 'line 2']));

        $result = $factory->run('ls -la');
        $this->assertEquals("line 1\nline 2\n", $result->output());

        // Array of output with empty line...
        $factory = new Factory;
        $factory->fake(fn () => $factory->result(['line 1', '', 'line 2']));

        $result = $factory->run('ls -la');
        $this->assertEquals("line 1\n\nline 2\n", $result->output());

        // Plain string...
        $factory = new Factory;
        $factory->fake(fn () => 'test output');

        $result = $factory->run('ls -la');
        $this->assertEquals("test output\n", $result->output());

        // Plain array...
        $factory = new Factory;
        $factory->fake(fn () => ['line 1', 'line 2']);

        $result = $factory->run('ls -la');
        $this->assertEquals("line 1\nline 2\n", $result->output());

        // Plain array with empty line...
        $factory = new Factory;
        $factory->fake(fn () => ['line 1', '', 'line 2']);

        $result = $factory->run('ls -la');
        $this->assertEquals("line 1\n\nline 2\n", $result->output());

        // Process description...
        $factory = new Factory;
        $factory->fake(fn () => $factory->describe()->output('line 1')->output('line 2'));

        $result = $factory->run('ls -la');
        $this->assertEquals("line 1\nline 2\n", $result->output());

        // Process description with empty line...
        $factory = new Factory;
        $factory->fake(fn () => $factory->describe()->output('line 1')->output('')->output('line 2'));

        $result = $factory->run('ls -la');
        $this->assertEquals("line 1\n\nline 2\n", $result->output());
    }

    public function testProcessFakeWithErrorOutput()
    {
        $factory = new Factory;
        $factory->fake(fn () => $factory->result('standard output', 'error output'));

        $result = $factory->run('ls -la');
        $this->assertEquals("standard output\n", $result->output());
        $this->assertEquals("error output\n", $result->errorOutput());

        // Array of error output...
        $factory = new Factory;
        $factory->fake(fn () => $factory->result('standard output', ['line 1', 'line 2']));

        $result = $factory->run('ls -la');
        $this->assertEquals("standard output\n", $result->output());
        $this->assertEquals("line 1\nline 2\n", $result->errorOutput());

        // Using process description...
        $factory = new Factory;
        $factory->fake(fn () => $factory->describe()->output('standard output')->errorOutput('error output'));

        $result = $factory->run('ls -la');
        $this->assertEquals("standard output\n", $result->output());
        $this->assertEquals("error output\n", $result->errorOutput());
    }

    public function testCustomizedFakesPerCommand()
    {
        $factory = new Factory;

        $factory->fake([
            'ls *' => 'ls command',
            'cat *' => 'cat command',
        ]);

        $result = $factory->run('ls -la');
        $this->assertEquals("ls command\n", $result->output());

        $result = $factory->run('cat composer.json');
        $this->assertEquals("cat command\n", $result->output());
    }

    public function testProcessFakeSequences()
    {
        $factory = new Factory;

        $factory->fake([
            'ls *' => $factory->sequence()
                ->push('ls command 1')
                ->push('ls command 2'),
            'cat *' => 'cat command',
        ]);

        $result = $factory->run('ls -la');
        $this->assertEquals("ls command 1\n", $result->output());

        $result = $factory->run('ls -la');
        $this->assertEquals("ls command 2\n", $result->output());

        $result = $factory->run('cat composer.json');
        $this->assertEquals("cat command\n", $result->output());
    }

    public function testProcessFakeSequencesCanReturnEmptyResultsWhenSequenceIsEmpty()
    {
        $factory = new Factory;

        $factory->fake([
            'ls *' => $factory->sequence()
                ->push('ls command 1')
                ->push('ls command 2')
                ->dontFailWhenEmpty(),
        ]);

        $result = $factory->run('ls -la');
        $this->assertEquals("ls command 1\n", $result->output());

        $result = $factory->run('ls -la');
        $this->assertEquals("ls command 2\n", $result->output());

        $result = $factory->run('ls -la');
        $this->assertEquals('', $result->output());
    }

    public function testProcessFakeSequencesCanThrowWhenSequenceIsEmpty()
    {
        $this->expectException(OutOfBoundsException::class);

        $factory = new Factory;

        $factory->fake([
            'ls *' => $factory->sequence()
                ->push('ls command 1')
                ->push('ls command 2'),
        ]);

        $result = $factory->run('ls -la');
        $this->assertEquals("ls command 1\n", $result->output());

        $result = $factory->run('ls -la');
        $this->assertEquals("ls command 2\n", $result->output());

        $result = $factory->run('ls -la');
    }

    public function testStrayProcessesCanBePreventedWithStringCommand()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Attempted process [');
        $this->expectExceptionMessage('cat composer.json');
        $this->expectExceptionMessage('] without a matching fake.');

        $factory = new Factory;

        $factory->preventStrayProcesses();

        $factory->fake([
            'ls *' => 'ls command',
        ]);

        $result = $factory->run('cat composer.json');
    }

    public function testStrayProcessesCanBePreventedWithArrayCommand()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Attempted process [');
        $this->expectExceptionMessage('cat composer.json');
        $this->expectExceptionMessage('] without a matching fake.');

        $factory = new Factory;

        $factory->preventStrayProcesses();

        $factory->fake([
            'ls *' => 'ls command',
        ]);

        $result = $factory->run(['cat composer.json']);
    }

    public function testStrayProcessesActuallyRunByDefault()
    {
        $factory = new Factory;

        $factory->fake([
            'cat *' => 'cat command',
        ]);

        $result = $factory->path(__DIR__)->run($this->ls());
        $this->assertTrue(str_contains($result->output(), 'ProcessTest.php'));
    }

    public function testProcessFakeThrowShorthand()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fake exception message');

        $factory = new Factory;

        $factory->fake(['cat me' => new \RuntimeException('fake exception message')]);

        $factory->run('cat me');
    }

    public function testFakeProcessesCanThrow()
    {
        $this->expectException(ProcessFailedException::class);

        $factory = new Factory;

        $factory->fake(fn () => $factory->result(exitCode: 1));

        $result = $factory->path(__DIR__)->run($this->ls());
        $result->throw();
    }

    public function testFakeProcessesThrowIfTrue()
    {
        $this->expectException(ProcessFailedException::class);

        $factory = new Factory;

        $factory->fake(fn () => $factory->result(exitCode: 1));

        $result = $factory->path(__DIR__)->run($this->ls());
        $result->throwIf(true);
    }

    public function testFakeProcessesDontThrowIfFalse()
    {
        $factory = new Factory;

        $factory->fake(fn () => $factory->result(exitCode: 1));

        $result = $factory->path(__DIR__)->run($this->ls());
        $result->throwIf(false);

        $this->assertTrue(true);
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    public function testRealProcessesCanHaveErrorOutput()
    {
        $factory = new Factory;
        $result = $factory->path(__DIR__)->run('echo "Hello World" >&2; exit 1;');

        $this->assertFalse($result->successful());
        $this->assertEquals('', $result->output());
        $this->assertEquals("Hello World\n", $result->errorOutput());
    }

    public function testFakeProcessesCanThrowWithoutOutput()
    {
        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessage(<<<'EOT'
            The command "exit 1;" failed.

            Exit Code: 1
            EOT
        );

        $factory = new Factory;
        $factory->fake(fn () => $factory->result(exitCode: 1));
        $result = $factory->path(__DIR__)->run('exit 1;');

        $result->throw();
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    public function testRealProcessesCanThrowWithoutOutput()
    {
        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessage(<<<'EOT'
            The command "exit 1;" failed.

            Exit Code: 1
            EOT
        );

        $factory = new Factory;
        $result = $factory->path(__DIR__)->run('exit 1;');

        $result->throw();
    }

    public function testFakeProcessesCanThrowWithErrorOutput()
    {
        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessage(<<<'EOT'
            The command "echo "Hello World" >&2; exit 1;" failed.

            Exit Code: 1

            Error Output:
            ================
            Hello World
            EOT
        );

        $factory = new Factory;
        $factory->fake(fn () => $factory->result(errorOutput: 'Hello World', exitCode: 1));
        $result = $factory->path(__DIR__)->run('echo "Hello World" >&2; exit 1;');

        $result->throw();
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    public function testRealProcessesCanThrowWithErrorOutput()
    {
        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessage(<<<'EOT'
            The command "echo "Hello World" >&2; exit 1;" failed.

            Exit Code: 1

            Error Output:
            ================
            Hello World
            EOT
        );

        $factory = new Factory;
        $result = $factory->path(__DIR__)->run('echo "Hello World" >&2; exit 1;');

        $result->throw();
    }

    public function testFakeProcessesCanThrowWithOutput()
    {
        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessage(<<<'EOT'
            The command "echo "Hello World" >&1; exit 1;" failed.

            Exit Code: 1

            Output:
            ================
            Hello World
            EOT
        );

        $factory = new Factory;
        $factory->fake(fn () => $factory->result(output: 'Hello World', exitCode: 1));
        $result = $factory->path(__DIR__)->run('echo "Hello World" >&1; exit 1;');

        $result->throw();
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    public function testRealProcessesCanThrowWithOutput()
    {
        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessage(<<<'EOT'
            The command "echo "Hello World" >&1; exit 1;" failed.

            Exit Code: 1

            Output:
            ================
            Hello World
            EOT
        );

        $factory = new Factory;
        $result = $factory->path(__DIR__)->run('echo "Hello World" >&1; exit 1;');

        $result->throw();
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    public function testRealProcessesCanTimeout()
    {
        $this->expectException(ProcessTimedOutException::class);
        $this->expectExceptionMessage(
            'The process "sleep 2; exit 1;" exceeded the timeout of 1 seconds.'
        );

        $factory = new Factory;
        $result = $factory->timeout(1)->path(__DIR__)->run('sleep 2; exit 1;');

        $result->throw();
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    public function testRealProcessesCanThrowIfTrue()
    {
        $this->expectException(ProcessFailedException::class);

        $factory = new Factory;
        $result = $factory->path(__DIR__)->run('echo "Hello World" >&2; exit 1;');

        $result->throwIf(true);
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    public function testRealProcessesDoesntThrowIfFalse()
    {
        $factory = new Factory;
        $result = $factory->path(__DIR__)->run('echo "Hello World" >&2; exit 1;');

        $result->throwIf(false);

        $this->assertTrue(true);
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    public function testRealProcessesCanUseStandardInput()
    {
        $factory = new Factory();
        $result = $factory->input('foobar')->run('cat');

        $this->assertSame('foobar', $result->output());
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    public function testProcessPipe()
    {
        $factory = new Factory;
        $factory->fake([
            'cat *' => "Hello, world\nfoo\nbar",
        ]);

        $pipe = $factory->pipe(function ($pipe) {
            $pipe->command('cat test');
            $pipe->command('grep -i "foo"');
        });

        $this->assertSame("foo\n", $pipe->output());
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    public function testProcessPipeFailed()
    {
        $factory = new Factory;
        $factory->fake([
            'cat *' => $factory->result(exitCode: 1),
        ]);

        $pipe = $factory->pipe(function ($pipe) {
            $pipe->command('cat test');
            $pipe->command('grep -i "foo"');
        });

        $this->assertTrue($pipe->failed());
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    public function testProcessSimplePipe()
    {
        $factory = new Factory;
        $factory->fake([
            'cat *' => "Hello, world\nfoo\nbar",
        ]);

        $pipe = $factory->pipe([
            'cat test',
            'grep -i "foo"',
        ]);

        $this->assertSame("foo\n", $pipe->output());
    }

    #[RequiresOperatingSystem('Linux|DAR')]
    public function testProcessSimplePipeFailed()
    {
        $factory = new Factory;
        $factory->fake([
            'cat *' => $factory->result(exitCode: 1),
        ]);

        $pipe = $factory->pipe([
            'cat test',
            'grep -i "foo"',
        ]);

        $this->assertTrue($pipe->failed());
    }

    public function testFakeInvokedProcessOutputWithLatestOutput()
    {
        $factory = new Factory;

        $factory->fake(function () use ($factory) {
            return $factory->describe()
                ->output('ONE')
                ->output('TWO')
                ->output('THREE')
                ->runsFor(iterations: 3);
        });

        $process = $factory->start('echo "ONE"; sleep 1; echo "TWO"; sleep 1; echo "THREE"; sleep 1;');

        $latestOutput = [];
        $output = [];

        while ($process->running()) {
            $latestOutput[] = $process->latestOutput();
            $output[] = $process->output();
        }

        $this->assertEquals("ONE\n", $latestOutput[0]);
        $this->assertEquals("ONE\nTWO\n", $output[0]);

        $this->assertEquals("THREE\n", $latestOutput[1]);
        $this->assertEquals("ONE\nTWO\nTHREE\n", $output[1]);

        $this->assertEquals('', $latestOutput[2]);
        $this->assertEquals("ONE\nTWO\nTHREE\n", $output[2]);
    }

    public function testFakeInvokedProcessWaitUntil()
    {
        $factory = new Factory;

        $factory->fake(function () use ($factory) {
            return $factory->describe()
                ->output('WAITING')
                ->output('READY')
                ->output('DONE')
                ->runsFor(iterations: 3);
        });

        $process = $factory->start('echo "WAITING"; sleep 1; echo "READY"; sleep 1; echo "DONE";');

        $callbackInvoked = [];

        $result = $process->waitUntil(function ($type, $buffer) use (&$callbackInvoked) {
            $callbackInvoked[] = $buffer;

            return str_contains($buffer, 'READY');
        });

        $this->assertInstanceOf(ProcessResult::class, $result);
        $this->assertTrue($result->successful());
        $this->assertContains("WAITING\n", $callbackInvoked);
        $this->assertContains("READY\n", $callbackInvoked);
    }

    public function testFakeInvokedProcessWaitUntilWithNoCallback()
    {
        $factory = new Factory;

        $factory->fake(function () use ($factory) {
            return $factory->describe()
                ->output('OUTPUT');
        });

        $process = $factory->start('echo "OUTPUT"');

        $result = $process->waitUntil();

        $this->assertInstanceOf(ProcessResult::class, $result);
        $this->assertTrue($result->successful());
        $this->assertEquals("OUTPUT\n", $result->output());
    }

    public function testFakeInvokedProcessWaitUntilWithErrorOutput()
    {
        $factory = new Factory;

        $factory->fake(function () use ($factory) {
            return $factory->describe()
                ->output('STDOUT')
                ->errorOutput('ERROR1')
                ->errorOutput('TARGET_ERROR')
                ->output('MORE_STDOUT')
                ->runsFor(iterations: 4);
        });

        $process = $factory->start('echo "STDOUT"; echo "ERROR1" >&2; echo "TARGET_ERROR" >&2; echo "MORE_STDOUT";');

        $callbackInvoked = [];

        $result = $process->waitUntil(function ($type, $buffer) use (&$callbackInvoked) {
            $callbackInvoked[] = [$type, $buffer];

            return str_contains($buffer, 'TARGET_ERROR');
        });

        $this->assertInstanceOf(ProcessResult::class, $result);
        $this->assertTrue($result->successful());
        $this->assertContains(['out', "STDOUT\n"], $callbackInvoked);
        $this->assertContains(['err', "ERROR1\n"], $callbackInvoked);
        $this->assertContains(['err', "TARGET_ERROR\n"], $callbackInvoked);
    }

    public function testFakeInvokedProcessWaitUntilCalledTwice()
    {
        $factory = new Factory;

        $factory->fake(function () use ($factory) {
            return $factory->describe()
                ->output('FIRST')
                ->output('SECOND')
                ->output('THIRD')
                ->output('FOURTH')
                ->runsFor(iterations: 4);
        });

        $process = $factory->start('echo "FIRST"; echo "SECOND"; echo "THIRD"; echo "FOURTH";');

        $firstCallbackInvoked = [];
        $secondCallbackInvoked = [];

        $firstResult = $process->waitUntil(function ($type, $buffer) use (&$firstCallbackInvoked) {
            $firstCallbackInvoked[] = $buffer;

            return str_contains($buffer, 'SECOND');
        });

        $this->assertInstanceOf(ProcessResult::class, $firstResult);
        $this->assertTrue($firstResult->successful());
        $this->assertContains("FIRST\n", $firstCallbackInvoked);
        $this->assertContains("SECOND\n", $firstCallbackInvoked);
        $this->assertCount(2, $firstCallbackInvoked);

        $secondResult = $process->waitUntil(function ($type, $buffer) use (&$secondCallbackInvoked) {
            $secondCallbackInvoked[] = $buffer;

            return str_contains($buffer, 'FOURTH');
        });

        $this->assertInstanceOf(ProcessResult::class, $secondResult);
        $this->assertTrue($secondResult->successful());
        $this->assertContains("THIRD\n", $secondCallbackInvoked);
        $this->assertContains("FOURTH\n", $secondCallbackInvoked);
        $this->assertCount(2, $secondCallbackInvoked);
    }

    public function testFakeInvokedProcessWaitUntilThatNeverMatches()
    {
        $factory = new Factory;

        $factory->fake(function () use ($factory) {
            return $factory->describe()
                ->output('LINE1')
                ->output('LINE2')
                ->output('LINE3')
                ->runsFor(iterations: 3);
        });

        $process = $factory->start('echo "LINE1"; echo "LINE2"; echo "LINE3";');

        $callbackInvoked = [];

        $result = $process->waitUntil(function ($type, $buffer) use (&$callbackInvoked) {
            $callbackInvoked[] = $buffer;

            return str_contains($buffer, 'NEVER_MATCHES');
        });

        $this->assertInstanceOf(ProcessResult::class, $result);
        $this->assertTrue($result->successful());
        $this->assertCount(3, $callbackInvoked);
        $this->assertContains("LINE1\n", $callbackInvoked);
        $this->assertContains("LINE2\n", $callbackInvoked);
        $this->assertContains("LINE3\n", $callbackInvoked);
    }

    public function testFakeInvokedProcessWaitUntilFollowedByWait()
    {
        $factory = new Factory;

        $factory->fake(function () use ($factory) {
            return $factory->describe()
                ->output('FIRST')
                ->output('SECOND')
                ->output('THIRD')
                ->runsFor(iterations: 3);
        });

        $process = $factory->start('echo "FIRST"; echo "SECOND"; echo "THIRD";');

        $waitUntilCallbacks = [];
        $waitCallbacks = [];

        $process->waitUntil(function ($type, $buffer) use (&$waitUntilCallbacks) {
            $waitUntilCallbacks[] = $buffer;

            return str_contains($buffer, 'FIRST');
        });

        $result = $process->wait(function ($type, $buffer) use (&$waitCallbacks) {
            $waitCallbacks[] = $buffer;
        });

        $this->assertInstanceOf(ProcessResult::class, $result);
        $this->assertTrue($result->successful());
        $this->assertCount(1, $waitUntilCallbacks);
        $this->assertEquals("FIRST\n", $waitUntilCallbacks[0]);
        $this->assertCount(2, $waitCallbacks);
        $this->assertContains("SECOND\n", $waitCallbacks);
        $this->assertContains("THIRD\n", $waitCallbacks);
    }

    public function testFakeInvokedProcessWaitCalledTwice()
    {
        $factory = new Factory;

        $factory->fake(function () use ($factory) {
            return $factory->describe()
                ->output('FIRST')
                ->output('SECOND')
                ->output('THIRD')
                ->runsFor(iterations: 3);
        });

        $process = $factory->start('echo "FIRST"; echo "SECOND"; echo "THIRD";');

        $firstCallbackInvoked = [];
        $secondCallbackInvoked = [];

        $firstResult = $process->wait(function ($type, $buffer) use (&$firstCallbackInvoked) {
            $firstCallbackInvoked[] = $buffer;
        });

        $this->assertInstanceOf(ProcessResult::class, $firstResult);
        $this->assertTrue($firstResult->successful());
        $this->assertCount(3, $firstCallbackInvoked);
        $this->assertContains("FIRST\n", $firstCallbackInvoked);
        $this->assertContains("SECOND\n", $firstCallbackInvoked);
        $this->assertContains("THIRD\n", $firstCallbackInvoked);

        $secondResult = $process->wait(function ($type, $buffer) use (&$secondCallbackInvoked) {
            $secondCallbackInvoked[] = $buffer;
        });

        $this->assertInstanceOf(ProcessResult::class, $secondResult);
        $this->assertTrue($secondResult->successful());
        $this->assertEmpty($secondCallbackInvoked);
    }

    public function testFakeInvokedProcessWaitFollowedByWaitUntil()
    {
        $factory = new Factory;

        $factory->fake(function () use ($factory) {
            return $factory->describe()
                ->output('FIRST')
                ->output('SECOND')
                ->output('THIRD')
                ->runsFor(iterations: 3);
        });

        $process = $factory->start('echo "FIRST"; echo "SECOND"; echo "THIRD";');

        $waitCallbacks = [];
        $waitUntilCallbacks = [];

        $process->wait(function ($type, $buffer) use (&$waitCallbacks) {
            $waitCallbacks[] = $buffer;
        });

        $result = $process->waitUntil(function ($type, $buffer) use (&$waitUntilCallbacks) {
            $waitUntilCallbacks[] = $buffer;

            return str_contains($buffer, 'THIRD');
        });

        $this->assertInstanceOf(ProcessResult::class, $result);
        $this->assertTrue($result->successful());
        $this->assertCount(3, $waitCallbacks);
        $this->assertEmpty($waitUntilCallbacks);
    }

    public function testBasicFakeAssertions()
    {
        $factory = new Factory;

        $factory->fake();

        $result = $factory->run('ls -la');

        $factory->assertRan(function ($process, $result) {
            return $process->command == 'ls -la';
        });

        $factory->assertRanTimes(function ($process, $result) {
            return $process->command == 'ls -la';
        }, 1);

        $factory->assertNotRan(function ($process, $result) {
            return $process->command == 'cat foo';
        });
    }

    public function testAssertingThatNothingRan()
    {
        $factory = new Factory;

        $factory->fake();

        $factory->assertNothingRan();
    }

    public function testProcessWithMultipleEnvironmentVariablesAndSequences()
    {
        $factory = new Factory;

        $factory->fake([
            'printenv TEST_VAR OTHER_VAR' => $factory->sequence()
                ->push("test_value\nother_value")
                ->push("new_test_value\nnew_other_value"),
        ]);

        $result = $factory->env([
            'TEST_VAR' => 'test_value',
            'OTHER_VAR' => 'other_value',
        ])->run('printenv TEST_VAR OTHER_VAR');

        $this->assertTrue($result->successful());
        $this->assertEquals("test_value\nother_value\n", $result->output());

        $result = $factory->env([
            'TEST_VAR' => 'new_test_value',
            'OTHER_VAR' => 'new_other_value',
        ])->run('printenv TEST_VAR OTHER_VAR');

        $this->assertTrue($result->successful());
        $this->assertEquals("new_test_value\nnew_other_value\n", $result->output());

        $factory->assertRanTimes(function ($process) {
            return str_contains($process->command, 'printenv TEST_VAR OTHER_VAR');
        }, 2);
    }

    protected function ls()
    {
        return windows_os() ? 'dir' : 'ls';
    }
}
