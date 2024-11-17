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
            'public const TABLENAME = \'foos\';',
            'Schema::table(self::TABLENAME, function (Blueprint $table) {',
        ], 'add_bar_to_foos_table.php');
    }

    public function testItCanGenerateMigrationFileWIthTableOption()
    {
        $this->artisan('make:migration', ['name' => 'AddBarToFoosTable', '--table' => 'foobar'])
            ->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'public const TABLENAME = \'foobar\';',
            'Schema::table(self::TABLENAME, function (Blueprint $table) {',
        ], 'add_bar_to_foos_table.php');
    }

    public function testItCanGenerateMigrationFileUsingCreateKeyword()
    {
        $this->artisan('make:migration', ['name' => 'CreateFoosTable'])
            ->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'public const TABLENAME = \'foos\';',
            'Schema::create(self::TABLENAME, function (Blueprint $table) {',
            'Schema::dropIfExists(self::TABLENAME);',
        ], 'create_foos_table.php');
    }

    public function testItCanGenerateMigrationFileUsingCreateOption()
    {
        $this->artisan('make:migration', ['name' => 'FoosTable', '--create' => 'foobar'])
            ->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'public const TABLENAME = \'foobar\';',
            'Schema::create(self::TABLENAME, function (Blueprint $table) {',
            'Schema::dropIfExists(self::TABLENAME);',
        ], 'foos_table.php');
    }
}
