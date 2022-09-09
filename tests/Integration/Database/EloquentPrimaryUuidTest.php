<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Concerns\HasPrimaryUuid;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentPrimaryUuidTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
        });
    }

    public function testUserWithPrimaryUuidCanBeCreated()
    {
        $user = UserWithPrimaryUuid::create();

        $this->assertTrue(Str::isUuid($user->id));
    }
}

class UserWithPrimaryUuid extends Eloquent
{
    use HasPrimaryUuid;

    protected $table = 'users';

    protected $guarded = [];
}
