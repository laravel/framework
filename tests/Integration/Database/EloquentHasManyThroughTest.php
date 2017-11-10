<?php

namespace Illuminate\Tests\Integration\Database\EloquentHasManyThroughTest;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class EloquentHasManyThroughTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

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

        $notMember = User::create(['name' => str_random()]);

        $this->assertEquals([$mate1->id, $mate2->id], $user->teamMates->pluck('id')->toArray());
        $this->assertEquals([$user->id], User::has('teamMates')->pluck('id')->toArray());
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
