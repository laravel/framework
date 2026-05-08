<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AfterQueryTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('team_id')->nullable();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('owner_id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('users_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('post_id');
            $table->timestamps();
        });
    }

    public function testAfterQueryOnEloquentBuilder()
    {
        AfterQueryUser::create();
        AfterQueryUser::create();

        $afterQueryIds = new Collection;

        $users = AfterQueryUser::query()
            ->afterQuery(function (Collection $users) use ($afterQueryIds) {
                $afterQueryIds->push(...$users->pluck('id')->all());

                $this->assertContainsOnlyInstancesOf(AfterQueryUser::class, $users);
            })
            ->get();

        $this->assertCount(2, $users);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $users->pluck('id')->toArray());
    }

    public function testAfterQueryOnBaseBuilder()
    {
        AfterQueryUser::create();
        AfterQueryUser::create();

        $afterQueryIds = new Collection;

        $users = AfterQueryUser::query()
            ->toBase()
            ->afterQuery(function (Collection $users) use ($afterQueryIds) {
                $afterQueryIds->push(...$users->pluck('id')->all());

                foreach ($users as $user) {
                    $this->assertNotInstanceOf(AfterQueryUser::class, $user);
                }
            })
            ->get();

        $this->assertCount(2, $users);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $users->pluck('id')->toArray());
    }

    public function testAfterQueryOnEloquentCursor()
    {
        AfterQueryUser::create();
        AfterQueryUser::create();

        $afterQueryIds = new Collection;

        $users = AfterQueryUser::query()
            ->afterQuery(function (Collection $users) use ($afterQueryIds) {
                $afterQueryIds->push(...$users->pluck('id')->all());

                $this->assertContainsOnlyInstancesOf(AfterQueryUser::class, $users);
            })
            ->cursor();

        $this->assertCount(2, $users);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $users->pluck('id')->toArray());
    }

    public function testAfterQueryOnBaseBuilderCursor()
    {
        AfterQueryUser::create();
        AfterQueryUser::create();

        $afterQueryIds = new Collection;

        $users = AfterQueryUser::query()
            ->toBase()
            ->afterQuery(function (Collection $users) use ($afterQueryIds) {
                $afterQueryIds->push(...$users->pluck('id')->all());

                foreach ($users as $user) {
                    $this->assertNotInstanceOf(AfterQueryUser::class, $user);
                }
            })
            ->cursor();

        $this->assertCount(2, $users);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $users->pluck('id')->toArray());
    }

    public function testAfterQueryOnEloquentPluck()
    {
        AfterQueryUser::create();
        AfterQueryUser::create();

        $afterQueryIds = new Collection;

        $userIds = AfterQueryUser::query()
            ->afterQuery(function (Collection $userIds) use ($afterQueryIds) {
                $afterQueryIds->push(...$userIds->all());

                foreach ($userIds as $userId) {
                    $this->assertIsInt($userId);
                }
            })
            ->pluck('id');

        $this->assertCount(2, $userIds);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $userIds->toArray());
    }

    public function testAfterQueryOnBaseBuilderPluck()
    {
        AfterQueryUser::create();
        AfterQueryUser::create();

        $afterQueryIds = new Collection;

        $userIds = AfterQueryUser::query()
            ->toBase()
            ->afterQuery(function (Collection $userIds) use ($afterQueryIds) {
                $afterQueryIds->push(...$userIds->all());

                foreach ($userIds as $userId) {
                    $this->assertIsInt((int) $userId);
                }
            })
            ->pluck('id');

        $this->assertCount(2, $userIds);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $userIds->toArray());
    }

    public function testAfterQueryHookOnBelongsToManyRelationship()
    {
        $user = AfterQueryUser::create();
        $firstPost = AfterQueryPost::create();
        $secondPost = AfterQueryPost::create();

        $user->posts()->attach($firstPost);
        $user->posts()->attach($secondPost);

        $afterQueryIds = new Collection;

        $posts = $user->posts()
            ->afterQuery(function (Collection $posts) use ($afterQueryIds) {
                $afterQueryIds->push(...$posts->pluck('id')->all());

                $this->assertContainsOnlyInstancesOf(AfterQueryPost::class, $posts);
            })
            ->get();

        $this->assertCount(2, $posts);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $posts->pluck('id')->toArray());
    }

    public function testAfterQueryKeyByOnEagerBelongsToManyRelationship()
    {
        $user = AfterQueryUser::create();
        $firstPost = AfterQueryPost::create();
        $secondPost = AfterQueryPost::create();

        $user->posts()->attach($firstPost);
        $user->posts()->attach($secondPost);

        $posts = AfterQueryUser::with('posts')->first()->posts;

        $this->assertEqualsCanonicalizing($posts->pluck('id')->toArray(), $posts->keys()->toArray());
    }

    public function testAfterQueryHookOnHasManyThroughRelationship()
    {
        $user = AfterQueryUser::create();
        $team = AfterQueryTeam::create(['owner_id' => $user->id]);

        AfterQueryUser::create(['team_id' => $team->id]);
        AfterQueryUser::create(['team_id' => $team->id]);

        $afterQueryIds = new Collection;

        $teamMates = $user->teamMates()
            ->afterQuery(function (Collection $teamMates) use ($afterQueryIds) {
                $afterQueryIds->push(...$teamMates->pluck('id')->all());

                $this->assertContainsOnlyInstancesOf(AfterQueryUser::class, $teamMates);
            })
            ->get();

        $this->assertCount(2, $teamMates);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $teamMates->pluck('id')->toArray());
    }

    public function testAfterQueryOnEloquentBuilderCanAlterReturnedResult()
    {
        $firstUser = AfterQueryUser::create();
        $secondUser = AfterQueryUser::create();

        $users = AfterQueryUser::query()
            ->afterQuery(function () {
                return new Collection(['foo', 'bar']);
            })
            ->get();

        $this->assertEquals(new Collection(['foo', 'bar']), $users);

        $users = AfterQueryUser::query()
            ->afterQuery(function () {
                return new Collection(['foo', 'bar']);
            })
            ->pluck('id');

        $this->assertEquals(new Collection(['foo', 'bar']), $users);

        $users = AfterQueryUser::query()
            ->afterQuery(function ($users) use ($firstUser) {
                return $users->first()->is($firstUser) ? new Collection(['foo', 'bar']) : new Collection(['bar', 'foo']);
            })
            ->cursor();

        $this->assertEquals(new Collection(['foo', 'bar']), $users->collect());

        $users = AfterQueryUser::query()
            ->afterQuery(function ($users) use ($firstUser) {
                return $users->where('id', '!=', $firstUser->id);
            })
            ->cursor();

        $this->assertEquals([$secondUser->id], $users->collect()->pluck('id')->all());

        $firstPost = AfterQueryPost::create();
        $secondPost = AfterQueryPost::create();

        $firstUser->posts()->attach($firstPost);
        $firstUser->posts()->attach($secondPost);

        $posts = $firstUser->posts()
            ->afterQuery(function () {
                return new Collection(['foo', 'bar']);
            })
            ->get();

        $this->assertEquals(new Collection(['foo', 'bar']), $posts);

        $user = AfterQueryUser::create();
        $team = AfterQueryTeam::create(['owner_id' => $user->id]);

        AfterQueryUser::create(['team_id' => $team->id]);
        AfterQueryUser::create(['team_id' => $team->id]);

        $teamMates = $user->teamMates()
            ->afterQuery(function () {
                return new Collection(['foo', 'bar']);
            })
            ->get();

        $this->assertEquals(new Collection(['foo', 'bar']), $teamMates);
    }

    public function testAfterQueryOnBaseBuilderCanAlterReturnedResult()
    {
        $firstUser = AfterQueryUser::create();
        $secondUser = AfterQueryUser::create();

        $users = AfterQueryUser::query()
            ->toBase()
            ->afterQuery(function () {
                return new Collection(['foo', 'bar']);
            })
            ->get();

        $this->assertEquals(new Collection(['foo', 'bar']), $users);

        $users = AfterQueryUser::query()
            ->toBase()
            ->afterQuery(function () {
                return new Collection(['foo', 'bar']);
            })
            ->pluck('id');

        $this->assertEquals(new Collection(['foo', 'bar']), $users);

        $users = AfterQueryUser::query()
            ->toBase()
            ->afterQuery(function ($users) use ($firstUser) {
                return ((int) $users->first()->id) === $firstUser->id ? new Collection(['foo', 'bar']) : new Collection(['bar', 'foo']);
            })
            ->cursor();

        $this->assertEquals(new Collection(['foo', 'bar']), $users->collect());

        $users = AfterQueryUser::query()
            ->toBase()
            ->afterQuery(function ($users) use ($firstUser) {
                return $users->where('id', '!=', $firstUser->id);
            })
            ->cursor();

        $this->assertEquals([$secondUser->id], $users->collect()->pluck('id')->all());

        $firstPost = AfterQueryPost::create();
        $secondPost = AfterQueryPost::create();

        $firstUser->posts()->attach($firstPost);
        $firstUser->posts()->attach($secondPost);

        $posts = $firstUser->posts()
            ->toBase()
            ->afterQuery(function () {
                return new Collection(['foo', 'bar']);
            })
            ->get();

        $this->assertEquals(new Collection(['foo', 'bar']), $posts);

        $user = AfterQueryUser::create();
        $team = AfterQueryTeam::create(['owner_id' => $user->id]);

        AfterQueryUser::create(['team_id' => $team->id]);
        AfterQueryUser::create(['team_id' => $team->id]);

        $teamMates = $user->teamMates()
            ->toBase()
            ->afterQuery(function () {
                return new Collection(['foo', 'bar']);
            })
            ->get();

        $this->assertEquals(new Collection(['foo', 'bar']), $teamMates);
    }
}

class AfterQueryUser extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;

    public function teamMates()
    {
        return $this->hasManyThrough(self::class, AfterQueryTeam::class, 'owner_id', 'team_id');
    }

    public function posts()
    {
        return $this->belongsToMany(AfterQueryPost::class, 'users_posts', 'user_id', 'post_id')
            ->afterQuery(fn (Collection $posts) => $posts->keyBy(fn (AfterQueryPost $post) => $post->id))
            ->withTimestamps();
    }
}

class AfterQueryTeam extends Model
{
    protected $table = 'teams';
    protected $guarded = [];
    public $timestamps = false;

    public function members()
    {
        return $this->hasMany(AfterQueryUser::class, 'team_id');
    }
}

class AfterQueryPost extends Model
{
    protected $table = 'posts';
    protected $guarded = [];
    public $timestamps = false;
}
