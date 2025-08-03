<?php

namespace Illuminate\Tests\Integration\Database\Postgres;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresOperatingSystem('Linux|Darwin')]
#[RequiresPhpExtension('pdo_pgsql')]
class DatabasePostgresSchemaBuilderAlterTableWithEnumTest extends PostgresTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name');
            $table->enum('status', ['pending', 'processing'])->default('pending');
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('orders');
    }

    public function testChangeEnumColumnValues()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'queued'])->default('pending')->change();
        });

        $this->assertTrue(Schema::hasColumn('orders', 'status'));
        $this->assertSame('varchar', Schema::getColumnType('orders', 'status'));
    }

    public function testRenameColumnOnTableWithEnum()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('name', 'title');
        });

        $this->assertTrue(Schema::hasColumn('orders', 'title'));
    }

    public function testChangeNonEnumColumnOnTableWithEnum()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('id')->change();
        });

        $this->assertSame('int4', Schema::getColumnType('orders', 'id'));
    }
}
