<?php

namespace Illuminate\Tests\Integration\Database\EloquentHasManyThroughTest;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentHasManyThroughTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->integer('team_id')->nullable();
            $table->string('name');
        });

        Schema::create('teams', function ($table) {
            $table->increments('id');
            $table->integer('owner_id');
        });
    }

    /**
     * @test
     */
    public function basic_create_and_retrieve()
    {
        $user = User::create(['name' => str_random()]);

        $team1 = Team::create(['owner_id' => $user->id]);
        $team2 = Team::create(['owner_id' => $user->id]);

        $mate1 = User::create(['name' => str_random(), 'team_id' => $team1->id]);
        $mate2 = User::create(['name' => str_random(), 'team_id' => $team2->id]);

        User::create(['name' => str_random()]);

        $this->assertEquals([$mate1->id, $mate2->id], $user->teamMates->pluck('id')->toArray());
        $this->assertEquals([$user->id], User::has('teamMates')->pluck('id')->toArray());
    }

    /**
     * @test
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     * @expectedExceptionMessage No query results for model [Illuminate\Tests\Integration\Database\EloquentHasManyThroughTest\User].
     */
    public function firstOrFail_method()
    {
        $user = User::create(['name' => str_random()]);
        $user->teamMates()->firstOrFail(['id' => 10]);
    }

    /**
     * @test
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     * @expectedExceptionMessage No query results for model [Illuminate\Tests\Integration\Database\EloquentHasManyThroughTest\User].
     */
    public function findOrFail_method()
    {
        $user = User::create(['name' => str_random()]);

        $user->teamMates()->findOrFail(10);
    }
}

class User extends Model
{
    public $table = 'users';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function teamMates()
    {
        return $this->hasManyThrough(self::class, Team::class, 'owner_id', 'team_id');
    }
}

class Team extends Model
{
    public $table = 'teams';
    public $timestamps = false;
    protected $guarded = ['id'];
}
