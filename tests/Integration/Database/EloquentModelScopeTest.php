<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\QualifiedScope;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentModelScopeTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('title');
            $table->boolean('published');
        });
    }

    public function testModelHasScope()
    {
        $model = new TestScopeModel1;

        $this->assertTrue($model->hasNamedScope('exists'));
    }

    public function testModelDoesNotHaveScope()
    {
        $model = new TestScopeModel1;

        $this->assertFalse($model->hasNamedScope('doesNotExist'));
    }

    public function testModelHasAttributedScope()
    {
        $model = new TestScopeModel1;

        $this->assertTrue($model->hasNamedScope('existsAsWell'));
    }

    public function testModelDoesNotHaveScopeWhenPrivateVisibility()
    {
        $model = new TestScopeModel1;

        $this->assertFalse($model->hasNamedScope('existsAsPrivate'));
    }

    public function testModelHasQualifiedScope()
    {
        $model = new TestScopePost;

        $this->assertTrue($model->hasNamedScope('published'));
        $this->assertFalse($model->hasNamedScope('publishedAsPrivate'));
    }

    public function testQualifiedScopeQualifiesWhereColumns()
    {
        $expected = TestScopePost::query()
            ->where('posts.published', true)
            ->where(['posts.title' => 'foo'])
            ->whereIn('posts.id', [1, 2])
            ->whereNull('posts.user_id')
            ->whereColumn('posts.id', '>', 'posts.user_id')
            ->whereBetweenColumns('posts.id', ['posts.first_id', 'posts.last_id'])
            ->whereValueBetween(5, ['posts.first_id', 'posts.last_id'])
            ->where(fn ($query) => $query->where('posts.title', 'bar')->orWhere('posts.title', 'baz'))
            ->where('users.id', 1)
            ->whereRaw('1 = 1')
            ->where(new Expression('lower(title)'), 'foo')
            ->whereExists(fn ($query) => $query->from('users')->where('id', 1));

        $query = TestScopePost::filtered();

        $this->assertSame($expected->toSql(), $query->toSql());
        $this->assertSame($expected->getBindings(), $query->getBindings());
    }

    public function testScopeDoesNotQualifyWhereColumns()
    {
        $this->assertSame(
            TestScopePost::where('published', true)->toSql(),
            TestScopePost::publishedWithoutQualifying()->toSql()
        );
    }

    public function testQualifiedScopeUsesTableAlias()
    {
        $this->assertSame(
            TestScopePost::from('posts as p')->where('p.published', true)->toSql(),
            TestScopePost::from('posts as p')->published()->toSql()
        );
    }

    public function testQualifiedScopeQualifiesWhereColumnsThroughRelation()
    {
        $user = TestScopeUser::create();

        $this->assertSame(
            $user->posts()->where('posts.published', true)->toSql(),
            $user->posts()->published()->toSql()
        );
    }

    public function testQualifiedScopeAvoidsAmbiguousColumnsWhenJoined()
    {
        TestScopePost::create(['title' => 'foo', 'published' => true]);
        TestScopePost::create(['title' => 'bar', 'published' => false]);

        $query = fn () => TestScopePost::select('posts.*')->join('posts as other', 'other.id', '=', 'posts.id');

        $this->assertThrows(fn () => $query()->publishedWithoutQualifying()->get(), QueryException::class);
        $this->assertSame(['foo'], $query()->published()->get()->pluck('title')->all());
    }
}

class TestScopeModel1 extends Model
{
    public function scopeExists(Builder $builder)
    {
        return $builder;
    }

    #[Scope]
    protected function existsAsWell(Builder $builder)
    {
        return $builder;
    }

    #[Scope]
    private function existsAsPrivate(Builder $builder)
    {
        return $builder;
    }
}

class TestScopeUser extends Model
{
    protected $table = 'users';

    public $timestamps = false;

    public function posts()
    {
        return $this->hasMany(TestScopePost::class, 'user_id');
    }
}

class TestScopePost extends Model
{
    protected $table = 'posts';

    protected $guarded = [];

    public $timestamps = false;

    #[QualifiedScope]
    protected function published(Builder $builder)
    {
        $builder->where('published', true);
    }

    #[Scope]
    protected function publishedWithoutQualifying(Builder $builder)
    {
        $builder->where('published', true);
    }

    #[QualifiedScope]
    private function publishedAsPrivate(Builder $builder)
    {
        $builder->where('published', true);
    }

    #[QualifiedScope]
    protected function filtered(Builder $builder)
    {
        $builder->where('published', true)
            ->where(['title' => 'foo'])
            ->whereIn('id', [1, 2])
            ->whereNull('user_id')
            ->whereColumn('id', '>', 'user_id')
            ->whereBetweenColumns('id', ['first_id', 'last_id'])
            ->whereValueBetween(5, ['first_id', 'last_id'])
            ->where(fn ($query) => $query->where('title', 'bar')->orWhere('title', 'baz'))
            ->where('users.id', 1)
            ->whereRaw('1 = 1')
            ->where(new Expression('lower(title)'), 'foo')
            ->whereExists(fn ($query) => $query->from('users')->where('id', 1));
    }
}
