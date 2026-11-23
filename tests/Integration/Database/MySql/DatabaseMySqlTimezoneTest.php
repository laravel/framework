<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresOperatingSystem('Linux|Darwin')]
#[RequiresPhpExtension('pdo_mysql')]
class DatabaseMySqlTimezoneTest extends MySqlTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $connection = $app['config']->get('database.default');

        $app['config']->set("database.connections.{$connection}.timezone", '+00:00');
    }

    protected function afterRefreshingDatabase()
    {
        Schema::create('timezone_test', function (Blueprint $table) {
            $table->id();
            $table->timestamp('written_by_laravel')->nullable();
            $table->timestamp('written_by_mysql')->useCurrent();
        });
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('timezone_test');
    }

    public function testConnectionUsesConfiguredSessionTimezone()
    {
        $this->assertSame('+00:00', DB::selectOne('select @@session.time_zone as tz')->tz);
    }

    public function testTimestampsWrittenByLaravelAndMysqlStoreTheSameInstant()
    {
        $now = Carbon::now()->startOfSecond();

        DB::table('timezone_test')->insert(['written_by_laravel' => $now]);

        $row = DB::selectOne('select UNIX_TIMESTAMP(written_by_laravel) as laravel, UNIX_TIMESTAMP(written_by_mysql) as mysql from timezone_test');

        $this->assertEqualsWithDelta($now->getTimestamp(), (int) $row->laravel, 1);
        $this->assertEqualsWithDelta((int) $row->mysql, (int) $row->laravel, 1);
    }

    public function testTimestampsFilledByMysqlAreReadBackInUtc()
    {
        $now = Carbon::now()->startOfSecond();

        DB::table('timezone_test')->insert(['written_by_laravel' => $now]);

        $readBack = Carbon::parse(DB::table('timezone_test')->value('written_by_mysql'), 'UTC');

        $this->assertEqualsWithDelta($now->getTimestamp(), $readBack->getTimestamp(), 5);
    }
}
