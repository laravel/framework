<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Concerns\HasPrimaryUlid;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentPrimaryUlidTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->timestamps();
        });
    }

    public function testUserWithPrimaryUuidCanBeCreated()
    {
        $user = UserWithPrimaryUlid::create();

        $this->assertTrue(Str::isUlid($user->id));
    }
}

/**
 * Eloquent Models...
 */
class UserWithPrimaryUlid extends Eloquent
{
    use HasPrimaryUlid;

    protected $table = 'users';

    protected $guarded = [];
}
