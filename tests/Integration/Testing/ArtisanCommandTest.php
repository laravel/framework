<?php

namespace Illuminate\Tests\Integration\Testing;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Mockery as m;
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

        Artisan::command('interactions', function () {
            /** @var Command $this */
            $this->ask('What is your name?');
            $this->choice('Which language do you prefer?', [
                'PHP',
                'PHP',
                'PHP',
            ]);

            $this->table(['Name', 'Email'], [
                ['Taylor Otwell', 'taylor@laravel.com'],
            ]);

            $this->confirm('Do you want to continue?', true);
        });

        Artisan::command('exit {code}', fn () => (int) $this->argument('code'));

        Artisan::command('contains', function () {
            $this->line('My name is Taylor Otwell');
        });
    }

    public function test_console_command_that_passes()
    {
        $this->artisan('exit', ['code' => 0])->assertOk();
    }

    public function test_console_command_that_fails()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Expected status code 0 but received 1.');

        $this->artisan('exit', ['code' => 1])->assertOk();
    }

    public function test_console_command_that_passes_with_output()
    {
        $this->artisan('survey')
             ->expectsQuestion('What is your name?', 'Taylor Otwell')
             ->expectsQuestion('Which language do you prefer?', 'PHP')
             ->expectsOutput('Your name is Taylor Otwell and you prefer PHP.')
             ->doesntExpectOutput('Your name is Taylor Otwell and you prefer Ruby.')
             ->assertExitCode(0);
    }

    public function test_console_command_that_passes_with_repeating_output()
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

    public function test_console_command_that_fails_from_unexpected_output()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Output "Your name is Taylor Otwell and you prefer PHP." was printed.');

        $this->artisan('survey')
             ->expectsQuestion('What is your name?', 'Taylor Otwell')
             ->expectsQuestion('Which language do you prefer?', 'PHP')
             ->doesntExpectOutput('Your name is Taylor Otwell and you prefer PHP.')
             ->assertExitCode(0);
    }

    public function test_console_command_that_fails_from_unexpected_output_substring()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Output "Taylor Otwell" was printed.');

        $this->artisan('contains')
             ->doesntExpectOutputToContain('Taylor Otwell')
             ->assertExitCode(0);
    }

    public function test_console_command_that_fails_from_missing_output()
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

    public function test_console_command_that_fails_from_exit_code_mismatch()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Expected status code 1 but received 0.');

        $this->artisan('survey')
             ->expectsQuestion('What is your name?', 'Taylor Otwell')
             ->expectsQuestion('Which language do you prefer?', 'PHP')
             ->assertExitCode(1);
    }

    public function test_console_command_that_fails_from_unordered_output()
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

    public function test_console_command_that_passes_if_the_output_contains()
    {
        $this->artisan('contains')
             ->expectsOutputToContain('Taylor Otwell')
             ->assertExitCode(0);
    }

    public function test_console_command_that_passes_if_outputs_something()
    {
        $this->artisan('contains')
            ->expectsOutput()
            ->assertExitCode(0);
    }

    public function test_console_command_that_passes_if_outputs_is_something_and_is_the_expected_output()
    {
        $this->artisan('contains')
            ->expectsOutput()
            ->expectsOutput('My name is Taylor Otwell')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fail_if_doesnt_output_something()
    {
        $this->expectException(InvalidCountException::class);

        $this->artisan('exit', ['code' => 0])
            ->expectsOutput()
            ->assertExitCode(0);

        m::close();
    }

    public function test_console_command_that_fail_if_doesnt_output_something_and_is_not_the_expected_output()
    {
        $this->expectException(AssertionFailedError::class);

        $this->ignoringMockOnceExceptions(function () {
            $this->artisan('exit', ['code' => 0])
                ->expectsOutput()
                ->expectsOutput('My name is Taylor Otwell')
                ->assertExitCode(0);
        });
    }

    public function test_console_command_that_passes_if_does_not_output_anything()
    {
        $this->artisan('exit', ['code' => 0])
            ->doesntExpectOutput()
            ->assertExitCode(0);
    }

    public function test_console_command_that_passes_if_does_not_output_anything_and_is_not_the_expected_output()
    {
        $this->artisan('exit', ['code' => 0])
            ->doesntExpectOutput()
            ->doesntExpectOutput('My name is Taylor Otwell')
            ->assertExitCode(0);
    }

    public function test_console_command_that_passes_if_expects_output_and_there_is_interactions()
    {
        $this->artisan('interactions', ['--no-interaction' => true])
            ->expectsOutput()
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsChoice('Which language do you prefer?', 'PHP', ['PHP', 'PHP', 'PHP'])
            ->expectsConfirmation('Do you want to continue?', true)
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_if_doesnt_expect_output_but__there_is_interactions()
    {
        $this->expectException(InvalidCountException::class);

        $this->artisan('interactions', ['--no-interaction' => true])
            ->doesntExpectOutput()
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsChoice('Which language do you prefer?', 'PHP', ['PHP', 'PHP', 'PHP'])
            ->expectsConfirmation('Do you want to continue?', true)
            ->assertExitCode(0);

        m::close();
    }

    public function test_console_command_that_fails_if_doesnt_expect_output_but_outputs_something()
    {
        $this->expectException(InvalidCountException::class);

        $this->artisan('contains')
            ->doesntExpectOutput()
            ->assertExitCode(0);

        m::close();
    }

    public function test_console_command_that_fails_if_doesnt_expect_output_and_does_expect_output()
    {
        $this->expectException(InvalidCountException::class);

        $this->artisan('contains')
            ->doesntExpectOutput()
            ->doesntExpectOutput('My name is Taylor Otwell')
            ->assertExitCode(0);

        m::close();
    }

    public function test_console_command_that_fails_if_the_output_does_not_contain()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Output does not contain "Otwell Taylor".');

        $this->ignoringMockOnceExceptions(function () {
            $this->artisan('contains')
                 ->expectsOutputToContain('Otwell Taylor')
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
                m::close();
            } catch (InvalidCountException) {
                // Ignore mock exception from PendingCommand::expectsOutput().
            }
        }
    }
}
