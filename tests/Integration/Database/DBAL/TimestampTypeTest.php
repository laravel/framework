<?php

namespace Illuminate\Tests\Integration\Database\DBAL;

use Illuminate\Database\DBAL\TimestampType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class TimestampTypeTest extends DatabaseTestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']['database.dbal.types'] = [
            'timestamp' => TimestampType::class,
        ];
    }

    public function testRegisterTimestampTypeOnConnection()
    {
        $this->assertTrue(
            $this->app['db']->connection()
                ->getDoctrineConnection()
                ->getDatabasePlatform()
                ->hasDoctrineTypeMappingFor('timestamp')
        );
    }

    public function testChangeDatetimeColumnToTimestampColumn()
    {
        Schema::create('test', function (Blueprint $table) {
            $table->addColumn('datetime', 'datetime_to_timestamp');
        });

        Schema::table('test', function (Blueprint $table) {
            $table->timestamp('datetime_to_timestamp')->nullable(true)->change();
        });

        $this->assertTrue(Schema::hasColumn('test', 'datetime_to_timestamp'));
        // Only Postgres and MySQL actually have a timestamp type
        $this->assertSame(
            match ($this->driver) {
                'mysql', 'pgsql' => 'timestamp',
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
            $table->dateTime('timestamp_to_datetime')->nullable(true)->change();
        });

        $this->assertTrue(Schema::hasColumn('test', 'timestamp_to_datetime'));
        // Postgres only has a timestamp type
        $this->assertSame(
            match ($this->driver) {
                'pgsql' => 'timestamp',
                'sqlsrv' => 'datetime2',
                default => 'datetime',
            },
            Schema::getColumnType('test', 'timestamp_to_datetime')
        );
    }

    public function testChangeStringColumnToTimestampColumn()
    {
        if ($this->driver !== 'mysql') {
            $this->markTestSkipped('Test requires a MySQL connection.');
        }

        Schema::create('test', function (Blueprint $table) {
            $table->string('string_to_timestamp');
        });

        $blueprint = new Blueprint('test', function ($table) {
            $table->timestamp('string_to_timestamp')->nullable(true)->change();
        });

        $queries = $blueprint->toSql($this->getConnection(), $this->getConnection()->getSchemaGrammar());

        $expected = ['alter table `test` modify `string_to_timestamp` timestamp null'];

        $this->assertEquals($expected, $queries);
    }
}
