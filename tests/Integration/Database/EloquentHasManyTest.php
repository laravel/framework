<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class EloquentHasManyTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function ($table) {
            $table->id();
        });

        Schema::create('logins', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->timestamp('login_time');
        });
    }

    public function testCanGetHasOneFromHasManyRelationship() {
        $user = User::create();

        $user->logins()->create(['login_time' => now()]);

        $this->assertInstanceOf(HasOne::class, $user->logins()->hasOne());
    }

    public function testHasOneRelationshipFromHasMany() {
        $user = User::create();

        Login::create(['user_id' => '99999'.$user->id, 'login_time' => '2020-09-29']);
        $latestLogin = Login::create(['user_id' => $user->id, 'login_time' => '2023-03-14']);
        $oldestLogin = Login::create(['user_id' => $user->id, 'login_time' => '2010-01-01']);


        $this->assertEquals($oldestLogin->id, $user->oldestLogin->id);
        $this->assertEquals($latestLogin->id, $user->latestLogin->id);
    }

}


class User extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    public function logins(): HasMany {
        return $this->hasMany(Login::class);
    }

    public function latestLogin(): HasOne
    {
        return $this->logins()->hasOne()->latestOfMany('login_time');
    }

    public function oldestLogin(): HasOne {
        return $this->logins()->hasOne()->oldestOfMany('login_time');
    }
}

class Login extends Model
{
    protected $guarded = [];
    public $timestamps = false;
}

