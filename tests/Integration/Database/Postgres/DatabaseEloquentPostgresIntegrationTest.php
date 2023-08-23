<?php

namespace Illuminate\Tests\Integration\Database\Postgres;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\WithEloquentIntegrationTests;

class DatabaseEloquentPostgresIntegrationTest extends PostgresTestCase
{
    use WithEloquentIntegrationTests;

    protected $eloquentModelClass = DatabaseEloquentPostgresIntegrationUser::class;

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        if (! Schema::hasTable('database_eloquent_postgres_integration_users')) {
            Schema::create('database_eloquent_postgres_integration_users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->timestamps();
            });
        }
    }

    protected function destroyDatabaseMigrations()
    {
        Schema::drop('database_eloquent_postgres_integration_users');
    }
}

class DatabaseEloquentPostgresIntegrationUser extends Model
{
    protected $table = 'database_eloquent_postgres_integration_users';

    protected $guarded = [];
}
