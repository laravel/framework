<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class DatabaseSchemaEnforceIncrementalPrimaryKeyTest extends TestCase
{
    public function testMigrationsTableHasPrimaryKey()
    {
        Builder::$enforceIncrementalPrimaryKey = true;

        Schema::create('table_with_required_id', function (Blueprint $table) {
            $table->id()->onlyWhenEnforced();
            $table->uuid();
            $table->text('name');
        });

        Builder::$enforceIncrementalPrimaryKey = false;

        Schema::create('table_with_optional_id', function (Blueprint $table) {
            $table->id()->onlyWhenEnforced();
            $table->uuid();
            $table->text('name');
        });

        $this->assertSame([
            'id' => 'integer',
            'uuid' => 'varchar',
            'name' => 'text',
        ], collect(Schema::getColumns('table_with_required_id'))->pluck('type', 'name')->all());

        $this->assertSame([
            'uuid' => 'varchar',
            'name' => 'text',
        ], collect(Schema::getColumns('table_with_optional_id'))->pluck('type', 'name')->all());
    }
}
