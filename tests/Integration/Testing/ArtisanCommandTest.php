<?php

namespace Illuminate\Tests\Integration\Testing;

use Illuminate\Support\Facades\Artisan;
use Mockery;
use Mockery\Exception\InvalidCountException;
use Mockery\Exception\InvalidOrderException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\AssertionFailedError;

class ArtisanCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::command('survey', function () {
            $name = $this->ask('What is your name?');

            $language = $this->choice('Which language do you prefer?', [
                'PHP',
                'Ruby',
                'Python',
            ]);

            $this->line("Your name is $name and you prefer $language.");
        });

        Artisan::command('slim', function () {
            $this->line($this->ask('Who?'));
            $this->line($this->ask('What?'));
            $this->line($this->ask('Huh?'));
        });
    }

    public function testConsoleCommandThatPasses()
    {
        $this->artisan('survey')
             ->expectsQuestion('What is your name?', 'Taylor Otwell')
             ->expectsQuestion('Which language do you prefer?', 'PHP')
             ->expectsOutput('Your name is Taylor Otwell and you prefer PHP.')
             ->doesntExpectOutput('Your name is Taylor Otwell and you prefer Ruby.')
             ->assertExitCode(0);
    }

    public function testConsoleCommandThatPassesWithRepeatingOutput()
    {
        $this->artisan('slim')
             ->expectsQuestion('Who?', 'Taylor')
             ->expectsQuestion('What?', 'Taylor')
             ->expectsQuestion('Huh?', 'Taylor')
             ->expectsOutput('Taylor')
             ->doesntExpectOutput('Otwell')
             ->expectsOutput('Taylor')
             ->expectsOutput('Taylor')
             ->assertExitCode(0);
    }

    public function testConsoleCommandThatFailsFromUnexpectedOutput()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Output "Your name is Taylor Otwell and you prefer PHP." was printed.');

        $this->artisan('survey')
             ->expectsQuestion('What is your name?', 'Taylor Otwell')
             ->expectsQuestion('Which language do you prefer?', 'PHP')
             ->doesntExpectOutput('Your name is Taylor Otwell and you prefer PHP.')
             ->assertExitCode(0);
    }

    public function testConsoleCommandThatFailsFromMissingOutput()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Output "Your name is Taylor Otwell and you prefer PHP." was not printed.');

        $this->ignoringMockOnceExceptions(function () {
            $this->artisan('survey')
                 ->expectsQuestion('What is your name?', 'Taylor Otwell')
                 ->expectsQuestion('Which language do you prefer?', 'Ruby')
                 ->expectsOutput('Your name is Taylor Otwell and you prefer PHP.')
                 ->assertExitCode(0);
        });
    }

    public function testConsoleCommandThatFailsFromExitCodeMismatch()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Expected status code 1 but received 0.');

        $this->artisan('survey')
             ->expectsQuestion('What is your name?', 'Taylor Otwell')
             ->expectsQuestion('Which language do you prefer?', 'PHP')
             ->assertExitCode(1);
    }

    public function testConsoleCommandThatFailsFromUnorderedOutput()
    {
        $this->expectException(InvalidOrderException::class);

        $this->ignoringMockOnceExceptions(function () {
            $this->artisan('slim')
                 ->expectsQuestion('Who?', 'Taylor')
                 ->expectsQuestion('What?', 'Danger')
                 ->expectsQuestion('Huh?', 'Otwell')
                 ->expectsOutput('Taylor')
                 ->expectsOutput('Otwell')
                 ->expectsOutput('Danger')
                 ->assertExitCode(0);
        });
    }

    /**
     * Don't allow Mockery's InvalidCountException to be reported. Mocks setup
     * in PendingCommand cause PHPUnit tearDown() to later throw the exception.
     *
     * @param  callable  $callback
     * @return void
     */
    protected function ignoringMockOnceExceptions(callable $callback)
    {
        try {
            $callback();
        } finally {
            try {
                Mockery::close();
            } catch (InvalidCountException $e) {
                // Ignore mock exception from PendingCommand::expectsOutput().
            }
        }
    }
}
