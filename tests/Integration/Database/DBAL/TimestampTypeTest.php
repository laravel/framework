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
        parent::defineEnvironment($app);

        $app['config']['database.dbal.types'] = [
            'timestamp' => TimestampType::class,
        ];
    }

    public function testRegisterTimestampTypeOnConnection()
    {
        $this->assertTrue(
            $this->app['db']->connection()
                ->getDoctrineSchemaManager()
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
        in_array($this->driver, ['pgsql', 'mysql'])
            ? $this->assertSame('timestamp', Schema::getColumnType('test', 'datetime_to_timestamp'))
            : $this->assertSame('datetime', Schema::getColumnType('test', 'datetime_to_timestamp'));
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
        $this->driver === 'pgsql'
            ? $this->assertSame('timestamp', Schema::getColumnType('test', 'timestamp_to_datetime'))
            : $this->assertSame('datetime', Schema::getColumnType('test', 'timestamp_to_datetime'));
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

        $expected = ['ALTER TABLE test CHANGE string_to_timestamp string_to_timestamp TIMESTAMP NULL DEFAULT NULL'];

        $this->assertEquals($expected, $queries);
    }
}
