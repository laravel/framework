<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
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

        Schema::create('eloquent_has_many_test_articles', function ($table) {
            $table->id();
            $table->string('title')->unique();
            $table->timestamps();
        });

        Schema::create('eloquent_has_many_test_article_author', function ($table) {
            $table->id();
            $table->foreignId('eloquent_has_many_test_user_id');
            $table->foreignId('eloquent_has_many_test_article_id');
            $table->timestamps();
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

    public function testCanGetHasManyFromBelongsToManyRelationship()
    {
        $user = EloquentHasManyTestUser::create();

        $article = EloquentHasManyTestArticle::create(['title' => Str::random()]);

        $user->articles()->attach($article);

        $this->assertInstanceOf(HasMany::class, $user->authorship());
    }

    public function testCanGetBelongsToManyFromHasManyRelationship()
    {
        $user = EloquentHasManyTestUser2::create();

        //dd($user->articles()->toSql(), $user->authorship()->toSql());

        $article = EloquentHasManyTestArticle::query()->create(['title' => Str::random()]);

        try {
            $user->articles()->attach($article);
        } catch (\Throwable $e) {
            dd($e);
        }

        $this->assertInstanceOf(BelongsToMany::class, $user->articles());
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

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(EloquentHasManyTestArticle::class, EloquentHasManyTestArticleAuthor::class)->using(EloquentHasManyTestArticleAuthor::class);
    }

    public function authorship(): HasMany
    {
        return $this->articles()->pivot();
    }
}

class EloquentHasManyTestUser2 extends Model
{
    protected $guarded = [];
    public $timestamps = false;
    protected $table = 'eloquent_has_many_test_users';

    public function getForeignKey()
    {
        return 'eloquent_has_many_test_user_id';
    }

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

    public function authorship(): HasMany
    {
        return $this->hasMany(EloquentHasManyTestArticleAuthor::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->authorship()->toMany(EloquentHasManyTestArticle::class);
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

class EloquentHasManyTestArticleAuthor extends Pivot
{
    protected $guarded = [];
}

class EloquentHasManyTestArticle extends Model
{
    protected $guarded = [];
}
