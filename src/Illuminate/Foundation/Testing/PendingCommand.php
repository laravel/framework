<?php

namespace Illuminate\Foundation\Testing;

use Mockery;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use Mockery\Exception\BadMethodCallException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Mockery\Exception\NoMatchingExpectationException;

class PendingCommand
{
    /**
     * The test being run.
     *
     * @var \Illuminate\Foundation\Testing\TestCase
     */
    public $test;

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    private $app;

    /**
     * The command to run.
     *
     * @var string
     */
    private $command;

    /**
     * The parameters to pass to the command.
     *
     * @var array
     */
    private $parameters;

    /**
     * Create a new pending console command run.
     *
     * @param  \Illuminate\Foundation\Testing\TestCase  $test
     * @param  \Illuminate\Foundation\Application  $app
     * @param  string  $command
     * @param  array  $parameters
     * @return void
     */
    public function __construct(TestCase $test, $app, $command, $parameters)
    {
        $this->test = $test;
        $this->app = $app;
        $this->command = $command;
        $this->parameters = $parameters;
    }

    /**
     * Specify a question that should be asked when the command runs.
     *
     * @param  string  $question
     * @param  string  $answer
     * @return $this
     */
    public function expectsQuestion($question, $answer)
    {
        $this->test->expectedQuestions[] = [$question, $answer];

        return $this;
    }

    /**
     * Specify an output that should be printed when the command runs.
     *
     * @param  string  $output
     * @return $this
     */
    public function expectsOutput($output)
    {
        $this->test->expectedOutput[] = $output;

        return $this;
    }

    /**
     * Mock the application's console output.
     *
     * @return void
     */
    private function mockTheConsoleOutput()
    {
        $mock = Mockery::mock(OutputStyle::class.'[askQuestion]', [
            (new ArrayInput($this->parameters)), $this->createABufferedOutputMock()
        ]);

        foreach ($this->test->expectedQuestions as $i => $question) {
            $mock->shouldReceive('askQuestion')
                ->once()
                ->ordered()
                ->with(Mockery::on(function ($argument) use ($question) {
                    return $argument->getQuestion() == $question[0];
                }))
                ->andReturnUsing(function () use ($question, $i) {
                    unset($this->test->expectedQuestions[$i]);

                    return $question[1];
                });
        }

        $this->app->bind(OutputStyle::class, function () use ($mock) {
            return $mock;
        });
    }

    /**
     * Create a mock for the buffered output.
     *
     * @return \Mockery\MockInterface
     */
    private function createABufferedOutputMock()
    {
        $mock = Mockery::mock(BufferedOutput::class.'[doWrite]')
                ->shouldAllowMockingProtectedMethods()
                ->shouldIgnoreMissing();

        foreach ($this->test->expectedOutput as $i => $output) {
            $mock->shouldReceive('doWrite')
                ->once()
                ->ordered()
                ->with($output, Mockery::any())
                ->andReturnUsing(function () use ($i) {
                    unset($this->test->expectedOutput[$i]);
                });
        }

        return $mock;
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->mockTheConsoleOutput();

        try {
            $this->app[Kernel::class]->call($this->command, $this->parameters);
        } catch (NoMatchingExpectationException $e) {
            if ($e->getMethodName() == 'askQuestion') {
                $this->test->fail('Unexpected question "'.$e->getActualArguments()[0]->getQuestion().'" was asked!');
            }
        } catch (BadMethodCallException $e) {
            if (str_contains($e->getMessage(), 'askQuestion')) {
                $this->test->fail('An an expected question was asked while running the command.');
            }
        }
    }
}
