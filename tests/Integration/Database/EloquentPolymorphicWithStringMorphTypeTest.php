<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\Assert;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;

#[WithMigration]
class EloquentPolymorphicWithStringMorphTypeTest extends DatabaseTestCase
{
    /** @inheritDoc */
    protected function setUp(): void
    {
        SchemaBuilder::morphUsingString();

        parent::setUp();
    }

    /** @inheritDoc */
    protected function tearDown(): void
    {
        parent::tearDown();

        SchemaBuilder::$defaultMorphKeyType = null;
    }

    /** @inheritDoc */
    protected function afterRefreshingDatabase()
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('owner');
            $table->string('provider');
        });

        $user = UserFactory::new()->create([
            'name' => 'Taylor Otwell',
            'email' => 'taylor@laravel.com',
            'password' => bcrypt('password'),
        ]);

        DB::table('integrations')->insert([
            'owner_type' => User::class,
            'owner_id' => $user->id,
            'provider' => 'dummy_provider'
        ]);
    }

    public function test_it_can_query_from_polymorphic_model()
    {
        $user = User::first();

        $user->loadMissing('integrations');

        Assert::assertArraySubset([
            ['owner_type' => User::class, 'owner_id' => $user->getKey(), 'provider' => 'dummy_provider'],
        ], Integration::where('owner_id', $user->id)->where('owner_type', User::class)->get()->toArray());
    }

    public function test_it_can_query_using_relationship()
    {
        $user = User::first();

        Assert::assertArraySubset([
            ['owner_type' => User::class, 'owner_id' => $user->getKey(), 'provider' => 'dummy_provider'],
        ], $user->integrations()->get()->toArray());
    }

    public function test_it_can_query_using_load_missing()
    {
        $user = User::query()->where('email', 'taylor@laravel.com')->first();

        $user->loadMissing('integrations');

        Assert::assertArraySubset([
            'name' => 'Taylor Otwell',
            'integrations' => [
                ['owner_type' => User::class, 'owner_id' => $user->getKey(), 'provider' => 'dummy_provider'],
            ],
        ], $user->toArray());
    }
}

class User extends Authenticatable
{
    protected $fillable = ['*'];

    public function integrations()
    {
        return $this->morphMany(Integration::class, 'owner');
    }
}

class Integration extends Model
{
    protected $fillable = ['*'];

    public function owner()
    {
        return $this->morphTo('owner');
    }
}

