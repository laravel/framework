<?php

namespace Illuminate\Tests\Integration\Generators;

class MigrateMakeCommandTest extends TestCase
{
    public function testItCanGenerateMigrationFile()
    {
        $this->artisan('make:migration', ['name' => 'AddBarToFoosTable'])
            ->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'Schema::table(\'foos\', function (Blueprint $table) {',
        ], 'add_bar_to_foos_table.php');
    }

    public function testItCanGenerateMigrationFileWIthTableOption()
    {
        $this->artisan('make:migration', ['name' => 'AddBarToFoosTable', '--table' => 'foobar'])
            ->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'Schema::table(\'foobar\', function (Blueprint $table) {',
        ], 'add_bar_to_foos_table.php');
    }

    public function testItCanGenerateMigrationFileUsingCreateKeyword()
    {
        $this->artisan('make:migration', ['name' => 'CreateFoosTable'])
            ->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'Schema::create(\'foos\', function (Blueprint $table) {',
            'Schema::dropIfExists(\'foos\');',
        ], 'create_foos_table.php');
    }

    public function testItCanGenerateMigrationFileUsingCreateOption()
    {
        $this->artisan('make:migration', ['name' => 'FoosTable', '--create' => 'foobar'])
            ->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'Schema::create(\'foobar\', function (Blueprint $table) {',
            'Schema::dropIfExists(\'foobar\');',
        ], 'foos_table.php');
    }
}
