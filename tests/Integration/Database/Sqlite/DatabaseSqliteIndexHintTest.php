<?php

namespace Illuminate\Tests\Integration\Database\Sqlite;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Orchestra\Testbench\Attributes\RequiresDatabase;
use PHPUnit\Framework\Attributes\DataProvider;

#[RequiresDatabase('sqlite')]
class DatabaseSqliteIndexHintTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->index();
            $table->integer('status')->index();
        });

        DB::table('posts')->insert([
            ['user_id' => 1, 'status' => 1],
            ['user_id' => 2, 'status' => 1],
        ]);
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('posts');
    }

    #[DataProvider('forceIndexDataProvider')]
    public function testForceIndex($index)
    {
        $this->assertSame(1, DB::table('posts')->forceIndex($index)->where('user_id', 1)->count());
    }

    public static function forceIndexDataProvider()
    {
        return [
            'named index' => ['posts_user_id_index'],
            'primary key' => ['PRIMARY'],
            'multiple indexes' => ['posts_user_id_index, posts_status_index'],
        ];
    }
}
