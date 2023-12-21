<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class DatabaseSchemaEnforceIncrementalPrimaryKeyTest extends TestCase
{
    public function testMigrationsTableHasPrimaryKey()
    {
        Builder::enforcePrimaryKey();

        Schema::create('table_with_required_id', function (Blueprint $table) {
            $table->id()->onlyWhenEnforced();
            $table->uuid();
            $table->text('name');
        });

        Builder::optionalPrimaryKey();

        Schema::create('table_with_optional_id', function (Blueprint $table) {
            $table->id()->onlyWhenEnforced();
            $table->uuid();
            $table->text('name');
        });

        $this->assertSame([
            'id',
            'uuid',
            'name',
        ], collect(Schema::getColumns('table_with_required_id'))->pluck('name')->all());

        $this->assertSame([
            'uuid',
            'name',
        ], collect(Schema::getColumns('table_with_optional_id'))->pluck('name')->all());
    }
}
