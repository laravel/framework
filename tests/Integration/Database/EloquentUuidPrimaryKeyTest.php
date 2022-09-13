<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentUuidPrimaryKeyTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
        });
    }

    public function testUserWithUuidPrimaryKeyCanBeCreated()
    {
        $user = UserWithUuidPrimaryKey::create();

        $this->assertTrue(Str::isUuid($user->id));
    }
}

class UserWithUuidPrimaryKey extends Eloquent
{
    use HasUuids;

    protected $table = 'users';

    protected $guarded = [];
}
