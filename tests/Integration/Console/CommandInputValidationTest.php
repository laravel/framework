<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Command;
use Orchestra\Testbench\TestCase;

class CommandInputValidationTest extends TestCase
{
    protected function setUp(): void
    {
        Artisan::starting(function ($artisan) {
            $artisan->resolveCommands([
                BasicCommandInputValidationStub::class,
                ArgumentsScopedValidationStub::class,
                OptionsScopedValidationStub::class,
                DashedOptionValidationStub::class,
                PlainKeyCollisionValidationStub::class,
                CustomMessagesAndAttributesValidationStub::class,
            ]);
        });

        BasicCommandInputValidationStub::$handled = false;
        PlainKeyCollisionValidationStub::$handled = false;

        parent::setUp();
    }

    public function testItFailsValidationForInvalidArgumentInputAndSkipsHandler(): void
    {
        $this->artisan('validation:basic')
            ->expectsOutputToContain('The name field is required.')
            ->assertExitCode(1);

        $this->assertFalse(BasicCommandInputValidationStub::$handled);
    }

    public function testItPassesValidationAndExecutesHandlerForValidInput(): void
    {
        $this->artisan('validation:basic', ['name' => 'Taylor'])
            ->assertSuccessful();

        $this->assertTrue(BasicCommandInputValidationStub::$handled);
    }

    public function testItValidatesUsingArgumentsNamespacedRules(): void
    {
        $this->artisan('validation:arguments')
            ->expectsOutputToContain('Argument scoped rule failed.')
            ->assertExitCode(1);
    }

    public function testItValidatesUsingOptionsNamespacedRules(): void
    {
        $this->artisan('validation:options')
            ->expectsOutputToContain('Option scoped rule failed.')
            ->assertExitCode(1);
    }

    public function testItValidatesUsingDashedOptionAliasRules(): void
    {
        $this->artisan('validation:dashed-option', ['--color' => 'green'])
            ->assertSuccessful();
    }

    public function testPlainKeyValidationUsesOptionValueWhenArgumentAndOptionShareAName(): void
    {
        $this->artisan('validation:collision', [
            'name' => 'argument-value',
            '--name' => 'option-value',
        ])->assertSuccessful();

        $this->assertTrue(PlainKeyCollisionValidationStub::$handled);
    }

    public function testItUsesCustomValidationMessagesAndAttributes(): void
    {
        $this->artisan('validation:custom', ['--count' => 1])
            ->expectsOutputToContain('Need at least 2 for record count.')
            ->assertExitCode(1);
    }
}

class BasicCommandInputValidationStub extends Command
{
    public static bool $handled = false;

    protected $signature = 'validation:basic {name?}';

    protected function rules(): array
    {
        return ['name' => 'required'];
    }

    public function handle(): int
    {
        static::$handled = true;

        return static::SUCCESS;
    }
}

class ArgumentsScopedValidationStub extends Command
{
    protected $signature = 'validation:arguments {name?}';

    protected function rules(): array
    {
        return ['arguments.name' => 'required'];
    }

    protected function messages(): array
    {
        return ['arguments.name.required' => 'Argument scoped rule failed.'];
    }

    public function handle(): int
    {
        return static::SUCCESS;
    }
}

class OptionsScopedValidationStub extends Command
{
    protected $signature = 'validation:options {--color=}';

    protected function rules(): array
    {
        return ['options.color' => 'required|in:green'];
    }

    protected function messages(): array
    {
        return ['options.color.required' => 'Option scoped rule failed.'];
    }

    public function handle(): int
    {
        return static::SUCCESS;
    }
}

class DashedOptionValidationStub extends Command
{
    protected $signature = 'validation:dashed-option {--color=}';

    protected function rules(): array
    {
        return ['--color' => 'required|in:green'];
    }

    public function handle(): int
    {
        return static::SUCCESS;
    }
}

class PlainKeyCollisionValidationStub extends Command
{
    public static bool $handled = false;

    protected $signature = 'validation:collision {name?} {--name=}';

    protected function rules(): array
    {
        return ['name' => 'in:option-value'];
    }

    public function handle(): int
    {
        static::$handled = true;

        return static::SUCCESS;
    }
}

class CustomMessagesAndAttributesValidationStub extends Command
{
    protected $signature = 'validation:custom {--count=}';

    protected function rules(): array
    {
        return ['options.count' => 'required|integer|min:2'];
    }

    protected function messages(): array
    {
        return ['options.count.min' => 'Need at least :min for :attribute.'];
    }

    protected function attributes(): array
    {
        return ['options.count' => 'record count'];
    }

    public function handle(): int
    {
        return static::SUCCESS;
    }
}
