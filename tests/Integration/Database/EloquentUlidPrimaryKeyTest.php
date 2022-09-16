<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentUlidPrimaryKeyTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->timestamps();
        });
    }

    public function testUserWithUlidPrimaryKeyCanBeCreated()
    {
        $user = UserWithUlidPrimaryKey::create();

        $this->assertTrue(Str::isUlid($user->id));
    }
}

class UserWithUlidPrimaryKey extends Eloquent
{
    use HasUlids;

    protected $table = 'users';

    protected $guarded = [];
}
