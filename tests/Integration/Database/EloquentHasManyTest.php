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
        Schema::create('eloquent_has_many_test_users', function ($table) {
            $table->id();
        });

        Schema::create('eloquent_has_many_test_logins', function ($table) {
            $table->id();
            $table->foreignId('eloquent_has_many_test_user_id');
            $table->timestamp('login_time');
        });
    }

    public function testCanGetHasOneFromHasManyRelationship()
    {
        $user = EloquentHasManyTestUser::create();

        $user->logins()->create(['login_time' => now()]);

        $this->assertInstanceOf(HasOne::class, $user->logins()->one());
    }

    public function testHasOneRelationshipFromHasMany()
    {
        $user = EloquentHasManyTestUser::create();

        EloquentHasManyTestLogin::create([
            'eloquent_has_many_test_user_id' => $user->id,
            'login_time' => '2020-09-29',
        ]);
        $latestLogin = EloquentHasManyTestLogin::create([
            'eloquent_has_many_test_user_id' => $user->id,
            'login_time' => '2023-03-14',
        ]);
        $oldestLogin = EloquentHasManyTestLogin::create([
            'eloquent_has_many_test_user_id' => $user->id,
            'login_time' => '2010-01-01',
        ]);

        $this->assertEquals($oldestLogin->id, $user->oldestLogin->id);
        $this->assertEquals($latestLogin->id, $user->latestLogin->id);
    }
}

class EloquentHasManyTestUser extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    public function logins(): HasMany
    {
        return $this->hasMany(EloquentHasManyTestLogin::class);
    }

    public function latestLogin(): HasOne
    {
        return $this->logins()->one()->latestOfMany('login_time');
    }

    public function oldestLogin(): HasOne
    {
        return $this->logins()->one()->oldestOfMany('login_time');
    }
}

class EloquentHasManyTestLogin extends Model
{
    protected $guarded = [];
    public $timestamps = false;
}
