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
            'function getTable()',
            'return \'foos\'',
            'Schema::table($this->getTable(), function (Blueprint $table) {',
        ], 'add_bar_to_foos_table.php');
    }

    public function testItCanGenerateMigrationFileWIthTableOption()
    {
        $this->artisan('make:migration', ['name' => 'AddBarToFoosTable', '--table' => 'foobar'])
            ->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'function getTable()',
            'return \'foobar\'',
            'Schema::table($this->getTable(), function (Blueprint $table) {',
        ], 'add_bar_to_foos_table.php');
    }

    public function testItCanGenerateMigrationFileUsingCreateKeyword()
    {
        $this->artisan('make:migration', ['name' => 'CreateFoosTable'])
            ->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'function getTable()',
            'return \'foos\'',
            'Schema::create($this->getTable(), function (Blueprint $table) {',
            'Schema::dropIfExists($this->getTable());',
        ], 'create_foos_table.php');
    }

    public function testItCanGenerateMigrationFileUsingCreateOption()
    {
        $this->artisan('make:migration', ['name' => 'FoosTable', '--create' => 'foobar'])
            ->assertExitCode(0);

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'function getTable()',
            'return \'foobar\'',
            'Schema::create($this->getTable(), function (Blueprint $table) {',
            'Schema::dropIfExists($this->getTable());',
        ], 'foos_table.php');
    }
}
