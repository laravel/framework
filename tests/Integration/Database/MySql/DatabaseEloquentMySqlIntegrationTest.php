<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\WithEloquentIntegrationTests;

class DatabaseEloquentMySqlIntegrationTest extends MySqlTestCase
{
    use WithEloquentIntegrationTests;

    protected $eloquentModelClass = DatabaseEloquentMySqlIntegrationUser::class;

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        if (! Schema::hasTable('database_eloquent_mysql_integration_users')) {
            Schema::create('database_eloquent_mysql_integration_users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->timestamps();
            });
        }
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('database_eloquent_mysql_integration_users');
    }
}

class DatabaseEloquentMySqlIntegrationUser extends Model
{
    protected $table = 'database_eloquent_mysql_integration_users';

    protected $guarded = [];
}
