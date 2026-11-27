<?php

namespace Illuminate\Tests\Integration\Testing;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use Throwable;

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

        Artisan::command('zero', function () {
            $this->line('0');
        });

        Artisan::command('new-england', function () {
            $this->line('The region of New England consists of the following states:');
            $this->info('Connecticut');
            $this->info('Maine');
            $this->info('Massachusetts');
            $this->info('New Hampshire');
            $this->info('Rhode Island');
            $this->info('Vermont');
        });
    }

    public function test_console_command_that_passes()
    {
        $this->artisan('exit', ['code' => 0])->assertOk();
    }

    public function test_console_command_that_fails()
    {
        $this->expectExceptionObject(new AssertionFailedError('Expected status code 0 but received 1.'));

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
        $this->expectExceptionObject(new AssertionFailedError('Output "Your name is Taylor Otwell and you prefer PHP." was printed.'));

        $this->artisan('survey')
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsQuestion('Which language do you prefer?', 'PHP')
            ->doesntExpectOutput('Your name is Taylor Otwell and you prefer PHP.')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_from_unexpected_output_substring()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "Taylor Otwell" was printed.'));

        $this->artisan('contains')
            ->doesntExpectOutputToContain('Taylor Otwell')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_from_zero_as_unexpected_output()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "0" was printed.'));

        $this->artisan('zero')
            ->doesntExpectOutput('0')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_from_zero_as_unexpected_output_substring()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "0" was printed.'));

        $this->artisan('zero')
            ->doesntExpectOutputToContain('0')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_from_missing_output()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "Your name is Taylor Otwell and you prefer PHP." was not printed.'));

        $this->artisan('survey')
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsQuestion('Which language do you prefer?', 'Ruby')
            ->expectsOutput('Your name is Taylor Otwell and you prefer PHP.')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_from_exit_code_mismatch()
    {
        $this->expectExceptionObject(new AssertionFailedError('Expected status code 1 but received 0.'));

        $this->artisan('survey')
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsQuestion('Which language do you prefer?', 'PHP')
            ->assertExitCode(1);
    }

    public function test_console_command_that_fails_from_unordered_output()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "Danger" was printed before "Otwell".'));

        $this->artisan('slim')
            ->expectsQuestion('Who?', 'Taylor')
            ->expectsQuestion('What?', 'Danger')
            ->expectsQuestion('Huh?', 'Otwell')
            ->expectsOutput('Taylor')
            ->expectsOutput('Otwell')
            ->expectsOutput('Danger')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_from_unexpected_question()
    {
        $this->expectExceptionObject(new AssertionFailedError('Unexpected question "What?" was asked.'));

        $this->artisan('slim')
            ->expectsQuestion('Who?', 'Taylor');
    }

    public function test_console_command_that_fails_from_unasked_question()
    {
        $this->expectExceptionObject(new AssertionFailedError('Question "When?" was not asked.'));

        $this->artisan('slim')
            ->expectsQuestion('Who?', 'Taylor')
            ->expectsQuestion('What?', 'Danger')
            ->expectsQuestion('Huh?', 'Otwell')
            ->expectsQuestion('When?', 'Now');
    }

    public function test_console_command_that_fails_from_unordered_questions()
    {
        $this->expectExceptionObject(new AssertionFailedError('Question "What?" was asked before "Huh?".'));

        $this->artisan('slim')
            ->expectsQuestion('Who?', 'Taylor')
            ->expectsQuestion('Huh?', 'Otwell')
            ->expectsQuestion('What?', 'Danger');
    }

    public function test_console_command_that_fails_from_unexpected_question_the_command_swallows()
    {
        Artisan::command('swallow', function () {
            try {
                $this->ask('Who?');
            } catch (Throwable) {
                //
            }
        });

        $this->expectExceptionObject(new AssertionFailedError('Unexpected question "Who?" was asked.'));

        $this->artisan('swallow');
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
        $this->expectExceptionObject(new AssertionFailedError('No output was printed.'));

        $this->artisan('exit', ['code' => 0])
            ->expectsOutput()
            ->assertExitCode(0);
    }

    public function test_console_command_that_fail_if_doesnt_output_something_and_is_not_the_expected_output()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "My name is Taylor Otwell" was not printed.'));

        $this->artisan('exit', ['code' => 0])
            ->expectsOutput()
            ->expectsOutput('My name is Taylor Otwell')
            ->assertExitCode(0);
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
        $this->expectExceptionObject(new AssertionFailedError('Output "+---------------+--------------------+" was printed.'));

        $this->artisan('interactions', ['--no-interaction' => true])
            ->doesntExpectOutput()
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsChoice('Which language do you prefer?', 'PHP', ['PHP', 'PHP', 'PHP'])
            ->expectsConfirmation('Do you want to continue?', true)
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_if_doesnt_expect_output_but_outputs_something()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "My name is Taylor Otwell" was printed.'));

        $this->artisan('contains')
            ->doesntExpectOutput()
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_if_doesnt_expect_output_and_does_expect_output()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "My name is Taylor Otwell" was printed.'));

        $this->artisan('contains')
            ->doesntExpectOutput()
            ->doesntExpectOutput('My name is Taylor Otwell')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_if_the_output_does_not_contain()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output does not contain "Otwell Taylor".'));

        $this->artisan('contains')
            ->expectsOutputToContain('Otwell Taylor')
            ->assertExitCode(0);
    }

    public function test_console_command_that_passes_if_one_line_contains_several_expected_substrings()
    {
        $this->artisan('contains')
            ->expectsOutput('My name is Taylor Otwell')
            ->expectsOutputToContain('Taylor')
            ->expectsOutputToContain('Otwell')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_if_expected_output_contains_an_unexpected_substring()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "Otwell" was printed.'));

        $this->artisan('contains')
            ->expectsOutputToContain('Taylor')
            ->doesntExpectOutputToContain('Otwell')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_if_outputs_something_unexpected()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "My name is Taylor Otwell" was printed.'));

        $this->artisan('contains')
            ->expectsOutput()
            ->doesntExpectOutput('My name is Taylor Otwell')
            ->assertExitCode(0);
    }

    public function test_console_command_that_passes_if_expected_output_repeats_after_later_output()
    {
        $this->artisan('slim')
            ->expectsQuestion('Who?', 'Taylor')
            ->expectsQuestion('What?', 'Otwell')
            ->expectsQuestion('Huh?', 'Taylor')
            ->expectsOutput('Taylor')
            ->expectsOutput('Otwell')
            ->assertExitCode(0);
    }

    public function test_console_command_that_passes_with_expected_table()
    {
        $this->artisan('interactions', ['--no-interaction' => true])
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsChoice('Which language do you prefer?', 'PHP', ['PHP', 'PHP', 'PHP'])
            ->expectsTable(['Name', 'Email'], [
                ['Taylor Otwell', 'taylor@laravel.com'],
            ])
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_with_different_table()
    {
        $this->expectExceptionObject(new AssertionFailedError('Output "| Taylor Otwell | taylor@laravel.org |" was not printed.'));

        $this->artisan('interactions', ['--no-interaction' => true])
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsChoice('Which language do you prefer?', 'PHP', ['PHP', 'PHP', 'PHP'])
            ->expectsTable(['Name', 'Email'], [
                ['Taylor Otwell', 'taylor@laravel.org'],
            ])
            ->expectsConfirmation('Do you want to continue?', 'yes')
            ->assertExitCode(0);
    }

    public function test_console_command_that_fails_with_different_choices()
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Question "Which language do you prefer?" has different options.');

        $this->artisan('survey')
            ->expectsQuestion('What is your name?', 'Taylor Otwell')
            ->expectsChoice('Which language do you prefer?', 'PHP', ['PHP', 'Ruby', 'Go']);
    }

    public function test_console_command_expectations_do_not_leak_into_nested_commands()
    {
        $test = $this;

        Artisan::command('outer', function () use ($test) {
            $this->line('Outer');

            $test->artisan('contains')->expectsOutput('My name is Taylor Otwell');

            $this->line('Done');
        });

        $this->artisan('outer')
            ->expectsOutput('Outer')
            ->expectsOutput('Done')
            ->assertExitCode(0);
    }

    public function test_console_command_output_expectation_does_not_carry_over_to_the_next_command()
    {
        $this->artisan('exit', ['code' => 0])
            ->doesntExpectOutput()
            ->assertExitCode(0);

        $this->artisan('contains')
            ->expectsOutput('My name is Taylor Otwell')
            ->assertExitCode(0);
    }

    public function test_pending_command_can_be_tapped()
    {
        $newEngland = [
            'Connecticut',
            'Maine',
            'Massachusetts',
            'New Hampshire',
            'Rhode Island',
            'Vermont',
        ];

        $this->artisan('new-england')
            ->expectsOutput('The region of New England consists of the following states:')
            ->tap(function ($command) use ($newEngland) {
                foreach ($newEngland as $state) {
                    $command->expectsOutput($state);
                }
            })
            ->assertExitCode(0);
    }

    public function test_console_command_returns_code_with_disabled_mocking()
    {
        $this->withoutMockingConsoleOutput();

        Assert::assertSame(0, $this->artisan('exit', ['code' => 0]));
    }

    public function test_mock_console_command_returns_pending_command_with_disabled_mocking()
    {
        $this->withoutMockingConsoleOutput();

        $this->mockArtisan('exit', ['code' => 0])->assertOk();
    }

    public function test_real_console_command_returns_code_with_enabled_mocking()
    {
        Assert::assertSame(0, $this->realArtisan('exit', ['code' => 0]));
    }
}
