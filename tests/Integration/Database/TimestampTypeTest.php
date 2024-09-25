<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;

class TimestampTypeTest extends DatabaseTestCase
{
    public function testChangeDatetimeColumnToTimestampColumn()
    {
        Schema::create('test', function (Blueprint $table) {
            $table->addColumn('datetime', 'datetime_to_timestamp');
        });

        Schema::table('test', function (Blueprint $table) {
            $table->timestamp('datetime_to_timestamp')->nullable()->change();
        });

        $this->assertTrue(Schema::hasColumn('test', 'datetime_to_timestamp'));
        // Only MySQL, MariaDB, and PostgreSQL actually have a timestamp type
        $this->assertSame(
            match ($this->driver) {
                'mysql', 'mariadb', 'pgsql' => 'timestamp',
                default => 'datetime',
            },
            Schema::getColumnType('test', 'datetime_to_timestamp')
        );
    }

    public function testChangeTimestampColumnToDatetimeColumn()
    {
        Schema::create('test', function (Blueprint $table) {
            $table->addColumn('timestamp', 'timestamp_to_datetime');
        });

        Schema::table('test', function (Blueprint $table) {
            $table->dateTime('timestamp_to_datetime')->nullable()->change();
        });

        $this->assertTrue(Schema::hasColumn('test', 'timestamp_to_datetime'));
        // Postgres only has a timestamp type
        $this->assertSame(
            match ($this->driver) {
                'pgsql' => 'timestamp',
                default => 'datetime',
            },
            Schema::getColumnType('test', 'timestamp_to_datetime')
        );
    }

    #[RequiresDatabase(['mysql', 'mariadb'])]
    public function testChangeStringColumnToTimestampColumn()
    {
        Schema::create('test', function (Blueprint $table) {
            $table->string('string_to_timestamp');
        });

        $blueprint = new Blueprint('test', function ($table) {
            $table->timestamp('string_to_timestamp')->nullable()->change();
        });

        $queries = $blueprint->toSql($this->getConnection(), $this->getConnection()->getSchemaGrammar());

        $expected = ['alter table `test` modify `string_to_timestamp` timestamp null'];

        $this->assertEquals($expected, $queries);
    }
}
