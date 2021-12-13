<?php

namespace Illuminate\Tests\Integration\Database\DBAL;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Database\DBAL\TimestampType;
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
    }
}
