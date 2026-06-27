<?php

namespace Illuminate\Tests\Integration\Database\EloquentEagerLoadGlobalScopeConstraintsTest;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentEagerLoadGlobalScopeConstraintsTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('eager_scope_users', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('eager_scope_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('eager_scope_user_id');
        });

        Schema::create('eager_scope_unrelateds', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('eager_scope_owner_id');
        });
    }

    protected function tearDown(): void
    {
        ScopeWithRelationQueryInConstructor::$capturedSql = null;

        parent::tearDown();
    }

    public function testRelationsBuiltInsideGlobalScopeConstructorKeepTheirConstraintsDuringEagerLoad()
    {
        EagerScopeUser::query()->getConnection()->table('eager_scope_users')->insert(['id' => 1]);
        EagerScopeUser::query()->getConnection()->table('eager_scope_posts')->insert(['id' => 1, 'eager_scope_user_id' => 1]);

        ScopeWithRelationQueryInConstructor::$capturedSql = null;

        EagerScopeUser::with('posts')->get();

        $this->assertNotNull(
            ScopeWithRelationQueryInConstructor::$capturedSql,
            'The global scope constructor did not run during the eager load.'
        );

        $this->assertStringContainsString(
            'eager_scope_owner_id',
            ScopeWithRelationQueryInConstructor::$capturedSql,
            'The relation built inside the global scope constructor lost its foreign key constraint during eager loading.'
        );
    }
}

class EagerScopeUser extends Model
{
    public $table = 'eager_scope_users';
    public $timestamps = false;
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(EagerScopePost::class, 'eager_scope_user_id');
    }
}

class EagerScopePost extends Model
{
    public $table = 'eager_scope_posts';
    public $timestamps = false;
    protected $guarded = [];

    protected static function booted()
    {
        static::addGlobalScope(new ScopeWithRelationQueryInConstructor());
    }
}

class EagerScopeUnrelated extends Model
{
    public $table = 'eager_scope_unrelateds';
    public $timestamps = false;
    protected $guarded = [];

    public function children()
    {
        return $this->hasMany(EagerScopeUnrelated::class, 'eager_scope_owner_id');
    }
}

class ScopeWithRelationQueryInConstructor implements Scope
{
    public static ?string $capturedSql = null;

    public function __construct()
    {
        $owner = new EagerScopeUnrelated();
        $owner->id = 99;

        static::$capturedSql = $owner->children()->toSql();
    }

    public function apply(Builder $builder, Model $model)
    {
        //
    }
}
