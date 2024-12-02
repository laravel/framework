<?php

namespace Illuminate\Tests\Integration\Generators;

class AbilityMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Abilities/FooAbility.php',
    ];

    public function testItCanGenerateAbilityFile()
    {
        $this->artisan('make:ability', ['name' => 'FooAbility'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Abilities;',
            'use Illuminate\Foundation\Auth\User;',
            'class FooAbility',
        ], 'app/Abilities/FooAbility.php');
    }

    public function testItCanGenerateAbilityFileWithModelOption()
    {
        $this->artisan('make:ability', ['name' => 'FooAbility', '--model' => 'Post'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Abilities;',
            'use App\Models\Post;',
            'use Illuminate\Foundation\Auth\User;',
            'class FooAbility',
            'public function __construct',
            'public Post $post',
            'public function granted(User $user)',
        ], 'app/Abilities/FooAbility.php');
    }
}
