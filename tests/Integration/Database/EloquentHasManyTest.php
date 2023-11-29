<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentHasManyTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('eloquent_has_many_test_users', function ($table) {
            $table->id();
        });

        Schema::create('eloquent_has_many_test_posts', function ($table) {
            $table->id();
            $table->foreignId('eloquent_has_many_test_user_id');
            $table->string('title')->unique();
            $table->timestamps();
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

    public function testFirstOrCreate()
    {
        $user = EloquentHasManyTestUser::create();

        $post1 = $user->posts()->create(['title' => Str::random()]);
        $post2 = $user->posts()->firstOrCreate(['title' => $post1->title]);

        $this->assertTrue($post1->is($post2));
        $this->assertCount(1, $user->posts()->get());
    }

    public function testFirstOrCreateWithinTransaction()
    {
        $user = EloquentHasManyTestUser::create();

        $post1 = $user->posts()->create(['title' => Str::random()]);

        DB::transaction(function () use ($user, $post1) {
            $post2 = $user->posts()->firstOrCreate(['title' => $post1->title]);

            $this->assertTrue($post1->is($post2));
        });

        $this->assertCount(1, $user->posts()->get());
    }

    public function testCreateOrFirst()
    {
        $user = EloquentHasManyTestUser::create();

        $post1 = $user->posts()->createOrFirst(['title' => Str::random()]);
        $post2 = $user->posts()->createOrFirst(['title' => $post1->title]);

        $this->assertTrue($post1->is($post2));
        $this->assertCount(1, $user->posts()->get());
    }

    public function testCreateOrFirstWithinTransaction()
    {
        $user = EloquentHasManyTestUser::create();

        $post1 = $user->posts()->create(['title' => Str::random()]);

        DB::transaction(function () use ($user, $post1) {
            $post2 = $user->posts()->createOrFirst(['title' => $post1->title]);

            $this->assertTrue($post1->is($post2));
        });

        $this->assertCount(1, $user->posts()->get());
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

    public function posts(): HasMany
    {
        return $this->hasMany(EloquentHasManyTestPost::class);
    }
}

class EloquentHasManyTestLogin extends Model
{
    protected $guarded = [];
    public $timestamps = false;
}

class EloquentHasManyTestPost extends Model
{
    protected $guarded = [];
}
