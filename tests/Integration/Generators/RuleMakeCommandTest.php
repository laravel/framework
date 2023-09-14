<?php

namespace Illuminate\Tests\Integration\Generators;

class RuleMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Rules/Foo.php',
    ];

    public function testItCanGenerateRuleFile()
    {
        $this->artisan('make:rule', ['name' => 'Foo'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Rules;',
            'use Illuminate\Contracts\Validation\ValidationRule;',
            'class Foo implements ValidationRule',
        ], 'app/Rules/Foo.php');
    }

    public function testItCanGenerateInvokableRuleFile()
    {
        $this->artisan('make:rule', ['name' => 'Foo'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Rules;',
            'use Illuminate\Contracts\Validation\ValidationRule;',
            'class Foo implements ValidationRule',
            'public function validate(string $attribute, mixed $value, Closure $fail): void',
        ], 'app/Rules/Foo.php');
    }

    public function testItCanGenerateImplicitRuleFile()
    {
        $this->artisan('make:rule', ['name' => 'Foo', '--implicit' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Rules;',
            'use Illuminate\Contracts\Validation\ValidationRule;',
            'class Foo implements ValidationRule',
            'public $implicit = true;',
            'public function validate(string $attribute, mixed $value, Closure $fail): void',
        ], 'app/Rules/Foo.php');
    }
}
