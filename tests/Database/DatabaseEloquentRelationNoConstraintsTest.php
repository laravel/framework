<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Tests\App\Models\Relationships\NoConstraintsTestToken;
use Illuminate\Tests\App\Models\Relationships\NoConstraintsTestUser;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentRelationNoConstraintsTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    protected function createSchema()
    {
        DB::schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        DB::schema()->create('tokens', function ($table) {
            $table->increments('id');
            $table->string('tokenable_type');
            $table->unsignedInteger('tokenable_id');
            $table->string('token');
        });
    }

    protected function tearDown(): void
    {
        NoConstraintsTestUser::$selectedToken = null;

        DB::schema()->drop('users');
        DB::schema()->drop('tokens');
    }

    public function testRelationshipAccessWhileResolvingEagerLoadPreservesConstraints()
    {
        $admin = NoConstraintsTestUser::create(['name' => 'Admin User']);
        $normalUser = NoConstraintsTestUser::create(['name' => 'Normal User']);

        NoConstraintsTestUser::$selectedToken = NoConstraintsTestToken::create([
            'tokenable_type' => NoConstraintsTestUser::class,
            'tokenable_id' => $normalUser->id,
            'token' => 'secret-token',
        ]);

        $users = NoConstraintsTestUser::with('selectedUserTokens')->get();

        $this->assertCount(0, $users->find($admin->id)->selectedUserTokens);
        $this->assertTrue(NoConstraintsTestUser::$selectedToken->is(
            $users->find($normalUser->id)->selectedUserTokens->sole()
        ));
    }

    public function testRelationshipAccessInsideExplicitNoConstraintsRemainsUnconstrained()
    {
        $admin = NoConstraintsTestUser::create(['name' => 'Admin User']);
        $normalUser = NoConstraintsTestUser::create(['name' => 'Normal User']);

        $token = NoConstraintsTestToken::create([
            'tokenable_type' => NoConstraintsTestUser::class,
            'tokenable_id' => $normalUser->id,
            'token' => 'secret-token',
        ]);

        $resolvedUser = Relation::noConstraints(fn () => $token->tokenable);

        $this->assertTrue($admin->is($resolvedUser));
    }
}
