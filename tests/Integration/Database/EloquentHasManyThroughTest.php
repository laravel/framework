<?php

namespace Illuminate\Tests\Integration\Database\EloquentHasManyThroughTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentHasManyThroughTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug')->nullable();
            $table->integer('team_id')->nullable();
            $table->string('name');
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('owner_id')->nullable();
            $table->string('owner_slug')->nullable();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->nullable();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id');
        });
    }

    public function testBasicCreateAndRetrieve()
    {
        $user = User::create(['name' => Str::random()]);

        $team1 = Team::create(['owner_id' => $user->id]);
        $team2 = Team::create(['owner_id' => $user->id]);

        $mate1 = User::create(['name' => 'John', 'team_id' => $team1->id]);
        $mate2 = User::create(['name' => 'Jack', 'team_id' => $team2->id, 'slug' => null]);

        User::create(['name' => Str::random()]);

        $this->assertEquals([$mate1->id, $mate2->id], $user->teamMates->pluck('id')->toArray());
        $this->assertEquals([$mate1->id, $mate2->id], $user->teamMatesWithPendingRelation->pluck('id')->toArray());
        $this->assertEquals([$user->id], User::has('teamMates')->pluck('id')->toArray());
        $this->assertEquals([$user->id], User::has('teamMatesWithPendingRelation')->pluck('id')->toArray());

        $result = $user->teamMates()->first();
        $this->assertEquals(
            $mate1->refresh()->getAttributes() + ['laravel_through_key' => '1'],
            $result->getAttributes()
        );

        $result = $user->teamMatesWithPendingRelation()->first();
        $this->assertEquals(
            $mate1->refresh()->getAttributes() + ['laravel_through_key' => '1'],
            $result->getAttributes()
        );

        $result = $user->teamMates()->firstWhere('name', 'Jack');
        $this->assertEquals(
            $mate2->refresh()->getAttributes() + ['laravel_through_key' => '1'],
            $result->getAttributes()
        );

        $result = $user->teamMatesWithPendingRelation()->firstWhere('name', 'Jack');
        $this->assertEquals(
            $mate2->refresh()->getAttributes() + ['laravel_through_key' => '1'],
            $result->getAttributes()
        );
    }

    public function testGlobalScopeColumns()
    {
        $user = User::create(['name' => Str::random()]);

        $team1 = Team::create(['owner_id' => $user->id]);

        User::create(['name' => Str::random(), 'team_id' => $team1->id]);

        $teamMates = $user->teamMatesWithGlobalScope;
        $this->assertEquals(['id' => 2, 'laravel_through_key' => 1], $teamMates[0]->getAttributes());

        $teamMates = $user->teamMatesWithGlobalScopeWithPendingRelation;
        $this->assertEquals(['id' => 2, 'laravel_through_key' => 1], $teamMates[0]->getAttributes());
    }

    public function testHasSelf()
    {
        $user = User::create(['name' => Str::random()]);

        $team = Team::create(['owner_id' => $user->id]);

        User::create(['name' => Str::random(), 'team_id' => $team->id]);

        $users = User::has('teamMates')->get();
        $this->assertCount(1, $users);

        $users = User::has('teamMatesWithPendingRelation')->get();
        $this->assertCount(1, $users);
    }

    public function testHasSelfCustomOwnerKey()
    {
        $user = User::create(['slug' => Str::random(), 'name' => Str::random()]);

        $team = Team::create(['owner_slug' => $user->slug]);

        User::create(['name' => Str::random(), 'team_id' => $team->id]);

        $users = User::has('teamMatesBySlug')->get();
        $this->assertCount(1, $users);

        $users = User::has('teamMatesBySlugWithPendingRelationship')->get();
        $this->assertCount(1, $users);
    }

    public function testHasSameParentAndThroughParentTable()
    {
        Category::create();
        Category::create();
        Category::create(['parent_id' => 1]);
        Category::create(['parent_id' => 2])->delete();

        Product::create(['category_id' => 3]);
        Product::create(['category_id' => 4]);

        $categories = Category::has('subProducts')->get();

        $this->assertEquals([1], $categories->pluck('id')->all());
    }

    public function testUpdateOrCreateAffectingWrongModelsRegression()
    {
        // On Laravel 10.21.0, a bug was introduced that would update the wrong model when using `updateOrCreate()`,
        // because the UPDATE statement would target a model based on the ID from the parent instead of the actual
        // conditions that the `updateOrCreate()` targeted. This test replicates the case that causes this bug.

        $team1 = Team::create();
        $team2 = Team::create();

        // Jane's ID should be the same as the $team1's ID for the bug to occur.
        $jane = User::create(['name' => 'Jane', 'slug' => 'jane-slug', 'team_id' => $team2->id]);
        $john = User::create(['name' => 'John', 'slug' => 'john-slug', 'team_id' => $team1->id]);

        $taylor = User::create(['name' => 'Taylor']);
        $team1->update(['owner_id' => $taylor->id]);

        $this->assertSame(2, $john->id);
        $this->assertSame(1, $jane->id);

        $this->assertSame(2, $john->refresh()->id);
        $this->assertSame(1, $jane->refresh()->id);

        $this->assertSame('john-slug', $john->slug);
        $this->assertSame('jane-slug', $jane->slug);

        $this->assertSame('john-slug', $john->refresh()->slug);
        $this->assertSame('jane-slug', $jane->refresh()->slug);

        // The `updateOrCreate` method would first try to find a matching attached record with a query like:
        // `->where($attributes)->first()`, which should return `John` of ID 1 in our case. However, it'd
        // return the incorrect ID of 2, which caused it to update Jane's record instead of John's.

        $taylor->teamMates()->updateOrCreate([
            'name' => 'John',
        ], [
            'slug' => 'john-doe',
        ]);

        // Expect $john's slug to be updated to john-doe instead of john-slug.
        $this->assertSame('john-doe', $john->fresh()->slug);
        // $jane should not be updated, because it belongs to a different user altogether.
        $this->assertSame('jane-slug', $jane->fresh()->slug);
    }
}

class User extends Model
{
    public $table = 'users';
    public $timestamps = false;
    protected $guarded = [];

    public function teamMates()
    {
        return $this->hasManyThrough(self::class, Team::class, 'owner_id', 'team_id');
    }

    public function teamMatesWithPendingRelation()
    {
        return $this->through($this->ownedTeams())
            ->has(fn (Team $team) => $team->members());
    }

    public function teamMatesBySlug()
    {
        return $this->hasManyThrough(self::class, Team::class, 'owner_slug', 'team_id', 'slug');
    }

    public function teamMatesBySlugWithPendingRelationship()
    {
        return $this->through($this->hasMany(Team::class, 'owner_slug', 'slug'))
            ->has(fn ($team) => $team->hasMany(User::class, 'team_id'));
    }

    public function teamMatesWithGlobalScope()
    {
        return $this->hasManyThrough(UserWithGlobalScope::class, Team::class, 'owner_id', 'team_id');
    }

    public function teamMatesWithGlobalScopeWithPendingRelation()
    {
        return $this->through($this->ownedTeams())
            ->has(fn (Team $team) => $team->membersWithGlobalScope());
    }

    public function ownedTeams()
    {
        return $this->hasMany(Team::class, 'owner_id');
    }
}

class UserWithGlobalScope extends Model
{
    public $table = 'users';
    public $timestamps = false;
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(function ($query) {
            $query->select('users.id');
        });
    }
}

class Team extends Model
{
    public $table = 'teams';
    public $timestamps = false;
    protected $guarded = [];

    public function members()
    {
        return $this->hasMany(User::class, 'team_id');
    }

    public function membersWithGlobalScope()
    {
        return $this->hasMany(UserWithGlobalScope::class, 'team_id');
    }
}

class Category extends Model
{
    use SoftDeletes;

    public $timestamps = false;
    protected $guarded = [];

    public function subProducts()
    {
        return $this->hasManyThrough(Product::class, self::class, 'parent_id');
    }
}

class Product extends Model
{
    public $timestamps = false;
    protected $guarded = [];
}
