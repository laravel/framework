<?php

namespace Illuminate\Tests\Integration\Database\Postgres;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseEloquentPostgresIntegrationTest extends PostgresTestCase
{
    protected function afterRefreshingDatabase()
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

    public function testCreateOrFirst()
    {
        $user1 = DatabaseEloquentPostgresIntegrationUser::createOrFirst(['email' => 'taylorotwell@gmail.com']);

        $this->assertSame('taylorotwell@gmail.com', $user1->email);
        $this->assertNull($user1->name);

        $user2 = DatabaseEloquentPostgresIntegrationUser::createOrFirst(
            ['email' => 'taylorotwell@gmail.com'],
            ['name' => 'Taylor Otwell']
        );

        $this->assertEquals($user1->id, $user2->id);
        $this->assertSame('taylorotwell@gmail.com', $user2->email);
        $this->assertNull($user2->name);

        $user3 = DatabaseEloquentPostgresIntegrationUser::createOrFirst(
            ['email' => 'abigailotwell@gmail.com'],
            ['name' => 'Abigail Otwell']
        );

        $this->assertNotEquals($user3->id, $user1->id);
        $this->assertSame('abigailotwell@gmail.com', $user3->email);
        $this->assertSame('Abigail Otwell', $user3->name);

        $user4 = DatabaseEloquentPostgresIntegrationUser::createOrFirst(
            ['name' => 'Dries Vints'],
            ['name' => 'Nuno Maduro', 'email' => 'nuno@laravel.com']
        );

        $this->assertSame('Nuno Maduro', $user4->name);
    }

    public function testCreateOrFirstWithinTransaction()
    {
        $user1 = DatabaseEloquentPostgresIntegrationUser::create(['email' => 'taylor@laravel.com']);

        DB::transaction(function () use ($user1) {
            $user2 = DatabaseEloquentPostgresIntegrationUser::createOrFirst(
                ['email' => 'taylor@laravel.com'],
                ['name' => 'Taylor Otwell']
            );

            $this->assertEquals($user1->id, $user2->id);
            $this->assertSame('taylor@laravel.com', $user2->email);
            $this->assertNull($user2->name);
        });
    }
}

class DatabaseEloquentPostgresIntegrationUser extends Model
{
    protected $table = 'database_eloquent_postgres_integration_users';

    protected $guarded = [];
}
