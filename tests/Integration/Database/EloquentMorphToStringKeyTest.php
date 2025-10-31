<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphToStringKeyTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphToStringKeyTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('integrations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('owner_type');
            $table->string('owner_id'); // String column for polymorphic key
            $table->string('provider');
            $table->timestamps();
        });
    }

    public function testMorphManyWithStringForeignKey()
    {
        $user = User::create(['name' => 'Test User']);

        $integration = new Integration([
            'provider' => 'github',
        ]);
        $integration->owner()->associate($user);
        $integration->save();

        // This should not throw a PostgreSQL type mismatch error
        $integrations = $user->integrations;

        $this->assertCount(1, $integrations);
        $this->assertEquals('github', $integrations->first()->provider);
    }

    public function testEagerLoadMorphManyWithStringForeignKey()
    {
        $user1 = User::create(['name' => 'User 1']);
        $user2 = User::create(['name' => 'User 2']);

        Integration::create([
            'owner_type' => User::class,
            'owner_id' => (string) $user1->id,
            'provider' => 'github',
        ]);

        Integration::create([
            'owner_type' => User::class,
            'owner_id' => (string) $user2->id,
            'provider' => 'gitlab',
        ]);

        // This should properly cast IDs to strings for PostgreSQL
        $users = User::with('integrations')->get();

        $this->assertCount(1, $users[0]->integrations);
        $this->assertCount(1, $users[1]->integrations);
    }

    public function testCreateViaMorphManyWithStringForeignKey()
    {
        $user = User::create(['name' => 'Test User']);

        $integration = $user->integrations()->create([
            'provider' => 'bitbucket',
        ]);

        // Verify the owner_id is stored as string
        $this->assertIsString($integration->owner_id);
        $this->assertEquals((string) $user->id, $integration->owner_id);
    }
}

class User extends Model
{
    public $table = 'users';
    public $timestamps = true;
    protected $guarded = [];

    public function integrations()
    {
        return $this->morphMany(Integration::class, 'owner');
    }
}

class Integration extends Model
{
    public $table = 'integrations';
    public $timestamps = true;
    protected $guarded = [];

    protected $casts = [
        'owner_id' => 'string',
    ];

    public function owner()
    {
        return $this->morphTo();
    }
}
