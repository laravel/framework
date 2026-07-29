<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
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

        DB::schema()->create('personal_access_tokens', function ($table) {
            $table->increments('id');
            $table->string('tokenable_type');
            $table->unsignedInteger('tokenable_id');
            $table->string('token');
        });
    }

    protected function tearDown(): void
    {
        DB::schema()->drop('users');
        DB::schema()->drop('personal_access_tokens');
    }

    public function testRelationshipAccessInsideNoConstraintsPreservesConstraints()
    {
        $admin = NoConstraintsTestUser::create(['name' => 'Admin User']);
        $normalUser = NoConstraintsTestUser::create(['name' => 'Normal User']);

        $token = NoConstraintsTestToken::create([
            'tokenable_type' => NoConstraintsTestUser::class,
            'tokenable_id' => $normalUser->id,
            'token' => 'secret-token',
        ]);

        $resolvedUser = null;

        Relation::noConstraints(function () use ($token, &$resolvedUser) {
            $resolvedUser = $token->tokenable;
        });

        $this->assertNotNull($resolvedUser);
        $this->assertEquals($normalUser->id, $resolvedUser->id);
    }
}

class NoConstraintsTestUser extends Model
{
    public $timestamps = false;
    protected $table = 'users';
    protected $guarded = [];
}

class NoConstraintsTestToken extends Model
{
    public $timestamps = false;
    protected $table = 'personal_access_tokens';
    protected $guarded = [];

    public function tokenable()
    {
        return $this->morphTo();
    }
}
