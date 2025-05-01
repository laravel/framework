<?php

namespace Illuminate\Tests\Database\EloquentRelationshipsTest;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentRelationshipsTest extends TestCase
{
    public function testStandardRelationships()
    {
        $post = new Post;

        $this->assertInstanceOf(HasOne::class, $post->attachment());
        $this->assertInstanceOf(BelongsTo::class, $post->author());
        $this->assertInstanceOf(HasMany::class, $post->comments());
        $this->assertInstanceOf(MorphOne::class, $post->owner());
        $this->assertInstanceOf(MorphMany::class, $post->likes());
        $this->assertInstanceOf(BelongsToMany::class, $post->viewers());
        $this->assertInstanceOf(HasManyThrough::class, $post->lovers());
        $this->assertInstanceOf(HasOneThrough::class, $post->contract());
        $this->assertInstanceOf(MorphToMany::class, $post->tags());
        $this->assertInstanceOf(MorphTo::class, $post->postable());
    }

    public function testOverriddenRelationships()
    {
        $post = new CustomPost;

        $this->assertInstanceOf(CustomHasOne::class, $post->attachment());
        $this->assertInstanceOf(CustomBelongsTo::class, $post->author());
        $this->assertInstanceOf(CustomHasMany::class, $post->comments());
        $this->assertInstanceOf(CustomMorphOne::class, $post->owner());
        $this->assertInstanceOf(CustomMorphMany::class, $post->likes());
        $this->assertInstanceOf(CustomBelongsToMany::class, $post->viewers());
        $this->assertInstanceOf(CustomHasManyThrough::class, $post->lovers());
        $this->assertInstanceOf(CustomHasOneThrough::class, $post->contract());
        $this->assertInstanceOf(CustomMorphToMany::class, $post->tags());
        $this->assertInstanceOf(CustomMorphTo::class, $post->postable());
    }

    public function testAlwaysUnsetBelongsToRelationWhenReceivedModelId()
    {
        // create users
        $user1 = (new FakeRelationship)->forceFill(['id' => 1]);
        $user2 = (new FakeRelationship)->forceFill(['id' => 2]);

        // sync user 1 using Model
        $post = new Post;
        $post->author()->associate($user1);
        $post->syncOriginal();

        // associate user 2 using Model
        $post->author()->associate($user2);
        $this->assertTrue($post->isDirty());
        $this->assertTrue($post->relationLoaded('author'));
        $this->assertSame($user2, $post->author);

        // associate user 1 using model ID
        $post->author()->associate($user1->id);
        $this->assertTrue($post->isClean());

        // we must unset relation even if attributes are clean
        $this->assertFalse($post->relationLoaded('author'));
    }

    public function testPendingHasThroughRelationship()
    {
        $fluent = (new FluentMechanic())->owner();
        $classic = (new ClassicMechanic())->owner();

        $this->assertInstanceOf(HasOneThrough::class, $classic);
        $this->assertInstanceOf(HasOneThrough::class, $fluent);
        $this->assertSame('m_id', $classic->getLocalKeyName());
        $this->assertSame('m_id', $fluent->getLocalKeyName());
        $this->assertSame('c_id', $classic->getSecondLocalKeyName());
        $this->assertSame('c_id', $fluent->getSecondLocalKeyName());
        $this->assertSame('mechanic_id', $classic->getFirstKeyName());
        $this->assertSame('mechanic_id', $fluent->getFirstKeyName());
        $this->assertSame('car_id', $classic->getForeignKeyName());
        $this->assertSame('car_id', $fluent->getForeignKeyName());
        $this->assertSame('classic_mechanics.m_id', $classic->getQualifiedLocalKeyName());
        $this->assertSame('fluent_mechanics.m_id', $fluent->getQualifiedLocalKeyName());
        $this->assertSame('cars.mechanic_id', $fluent->getQualifiedFirstKeyName());
        $this->assertSame('cars.mechanic_id', $classic->getQualifiedFirstKeyName());

        $fluent = (new FluentProject())->deployments();
        $classic = (new ClassicProject())->deployments();

        $this->assertInstanceOf(HasManyThrough::class, $classic);
        $this->assertInstanceOf(HasManyThrough::class, $fluent);
        $this->assertSame('p_id', $classic->getLocalKeyName());
        $this->assertSame('p_id', $fluent->getLocalKeyName());
        $this->assertSame('e_id', $classic->getSecondLocalKeyName());
        $this->assertSame('e_id', $fluent->getSecondLocalKeyName());
        $this->assertSame('pro_id', $classic->getFirstKeyName());
        $this->assertSame('pro_id', $fluent->getFirstKeyName());
        $this->assertSame('env_id', $classic->getForeignKeyName());
        $this->assertSame('env_id', $fluent->getForeignKeyName());
        $this->assertSame('classic_projects.p_id', $classic->getQualifiedLocalKeyName());
        $this->assertSame('fluent_projects.p_id', $fluent->getQualifiedLocalKeyName());
        $this->assertSame('environments.pro_id', $fluent->getQualifiedFirstKeyName());
        $this->assertSame('environments.pro_id', $classic->getQualifiedFirstKeyName());

        $fluent = (new FluentProject())->environmentData();
        $classic = (new ClassicProject())->environmentData();

        $this->assertInstanceOf(HasManyThrough::class, $classic);
        $this->assertInstanceOf(HasManyThrough::class, $fluent);
        $this->assertSame('p_id', $classic->getLocalKeyName());
        $this->assertSame('p_id', $fluent->getLocalKeyName());
        $this->assertSame('e_id', $classic->getSecondLocalKeyName());
        $this->assertSame('e_id', $fluent->getSecondLocalKeyName());
        $this->assertSame('pro_id', $classic->getFirstKeyName());
        $this->assertSame('pro_id', $fluent->getFirstKeyName());
        $this->assertSame('env_id', $classic->getForeignKeyName());
        $this->assertSame('env_id', $fluent->getForeignKeyName());
        $this->assertSame('classic_projects.p_id', $classic->getQualifiedLocalKeyName());
        $this->assertSame('fluent_projects.p_id', $fluent->getQualifiedLocalKeyName());
        $this->assertSame('environments.pro_id', $fluent->getQualifiedFirstKeyName());
        $this->assertSame('environments.pro_id', $classic->getQualifiedFirstKeyName());
    }

    public function testStringyHasThroughApi()
    {
        $fluent = (new FluentMechanic())->owner();
        $stringy = (new class extends FluentMechanic
        {
            public function owner()
            {
                return $this->through('car')->has('owner');
            }

            public function getTable()
            {
                return 'stringy_mechanics';
            }
        })->owner();

        $this->assertInstanceOf(HasOneThrough::class, $fluent);
        $this->assertInstanceOf(HasOneThrough::class, $stringy);
        $this->assertSame('m_id', $fluent->getLocalKeyName());
        $this->assertSame('m_id', $stringy->getLocalKeyName());
        $this->assertSame('c_id', $fluent->getSecondLocalKeyName());
        $this->assertSame('c_id', $stringy->getSecondLocalKeyName());
        $this->assertSame('mechanic_id', $fluent->getFirstKeyName());
        $this->assertSame('mechanic_id', $stringy->getFirstKeyName());
        $this->assertSame('car_id', $fluent->getForeignKeyName());
        $this->assertSame('car_id', $stringy->getForeignKeyName());
        $this->assertSame('fluent_mechanics.m_id', $fluent->getQualifiedLocalKeyName());
        $this->assertSame('stringy_mechanics.m_id', $stringy->getQualifiedLocalKeyName());
        $this->assertSame('cars.mechanic_id', $stringy->getQualifiedFirstKeyName());
        $this->assertSame('cars.mechanic_id', $fluent->getQualifiedFirstKeyName());

        $fluent = (new FluentProject())->deployments();
        $stringy = (new class extends FluentProject
        {
            public function deployments()
            {
                return $this->through('environments')->has('deployments');
            }

            public function getTable()
            {
                return 'stringy_projects';
            }
        })->deployments();

        $this->assertInstanceOf(HasManyThrough::class, $fluent);
        $this->assertInstanceOf(HasManyThrough::class, $stringy);
        $this->assertSame('p_id', $fluent->getLocalKeyName());
        $this->assertSame('p_id', $stringy->getLocalKeyName());
        $this->assertSame('e_id', $fluent->getSecondLocalKeyName());
        $this->assertSame('e_id', $stringy->getSecondLocalKeyName());
        $this->assertSame('pro_id', $fluent->getFirstKeyName());
        $this->assertSame('pro_id', $stringy->getFirstKeyName());
        $this->assertSame('env_id', $fluent->getForeignKeyName());
        $this->assertSame('env_id', $stringy->getForeignKeyName());
        $this->assertSame('fluent_projects.p_id', $fluent->getQualifiedLocalKeyName());
        $this->assertSame('stringy_projects.p_id', $stringy->getQualifiedLocalKeyName());
        $this->assertSame('environments.pro_id', $stringy->getQualifiedFirstKeyName());
        $this->assertSame('environments.pro_id', $fluent->getQualifiedFirstKeyName());
    }

    public function testHigherOrderHasThroughApi()
    {
        $fluent = (new FluentMechanic())->owner();
        $higher = (new class extends FluentMechanic
        {
            public function owner()
            {
                return $this->throughCar()->hasOwner();
            }

            public function getTable()
            {
                return 'higher_mechanics';
            }
        })->owner();

        $this->assertInstanceOf(HasOneThrough::class, $fluent);
        $this->assertInstanceOf(HasOneThrough::class, $higher);
        $this->assertSame('m_id', $fluent->getLocalKeyName());
        $this->assertSame('m_id', $higher->getLocalKeyName());
        $this->assertSame('c_id', $fluent->getSecondLocalKeyName());
        $this->assertSame('c_id', $higher->getSecondLocalKeyName());
        $this->assertSame('mechanic_id', $fluent->getFirstKeyName());
        $this->assertSame('mechanic_id', $higher->getFirstKeyName());
        $this->assertSame('car_id', $fluent->getForeignKeyName());
        $this->assertSame('car_id', $higher->getForeignKeyName());
        $this->assertSame('fluent_mechanics.m_id', $fluent->getQualifiedLocalKeyName());
        $this->assertSame('higher_mechanics.m_id', $higher->getQualifiedLocalKeyName());
        $this->assertSame('cars.mechanic_id', $higher->getQualifiedFirstKeyName());
        $this->assertSame('cars.mechanic_id', $fluent->getQualifiedFirstKeyName());

        $fluent = (new FluentProject())->deployments();
        $higher = (new class extends FluentProject
        {
            public function deployments()
            {
                return $this->throughEnvironments()->hasDeployments();
            }

            public function getTable()
            {
                return 'higher_projects';
            }
        })->deployments();

        $this->assertInstanceOf(HasManyThrough::class, $fluent);
        $this->assertInstanceOf(HasManyThrough::class, $higher);
        $this->assertSame('p_id', $fluent->getLocalKeyName());
        $this->assertSame('p_id', $higher->getLocalKeyName());
        $this->assertSame('e_id', $fluent->getSecondLocalKeyName());
        $this->assertSame('e_id', $higher->getSecondLocalKeyName());
        $this->assertSame('pro_id', $fluent->getFirstKeyName());
        $this->assertSame('pro_id', $higher->getFirstKeyName());
        $this->assertSame('env_id', $fluent->getForeignKeyName());
        $this->assertSame('env_id', $higher->getForeignKeyName());
        $this->assertSame('fluent_projects.p_id', $fluent->getQualifiedLocalKeyName());
        $this->assertSame('higher_projects.p_id', $higher->getQualifiedLocalKeyName());
        $this->assertSame('environments.pro_id', $higher->getQualifiedFirstKeyName());
        $this->assertSame('environments.pro_id', $fluent->getQualifiedFirstKeyName());
    }
}

class FakeRelationship extends Model
{
    //
}

class Post extends Model
{
    public function attachment()
    {
        return $this->hasOne(FakeRelationship::class);
    }

    public function author()
    {
        return $this->belongsTo(FakeRelationship::class);
    }

    public function comments()
    {
        return $this->hasMany(FakeRelationship::class);
    }

    public function likes()
    {
        return $this->morphMany(FakeRelationship::class, 'actionable');
    }

    public function owner()
    {
        return $this->morphOne(FakeRelationship::class, 'property');
    }

    public function viewers()
    {
        return $this->belongsToMany(FakeRelationship::class);
    }

    public function lovers()
    {
        return $this->hasManyThrough(FakeRelationship::class, FakeRelationship::class);
    }

    public function contract()
    {
        return $this->hasOneThrough(FakeRelationship::class, FakeRelationship::class);
    }

    public function tags()
    {
        return $this->morphToMany(FakeRelationship::class, 'taggable');
    }

    public function postable()
    {
        return $this->morphTo();
    }
}

class CustomPost extends Post
{
    protected function newBelongsTo(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        return new CustomBelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
    }

    protected function newHasMany(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new CustomHasMany($query, $parent, $foreignKey, $localKey);
    }

    protected function newHasOne(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        return new CustomHasOne($query, $parent, $foreignKey, $localKey);
    }

    protected function newMorphOne(Builder $query, Model $parent, $type, $id, $localKey)
    {
        return new CustomMorphOne($query, $parent, $type, $id, $localKey);
    }

    protected function newMorphMany(Builder $query, Model $parent, $type, $id, $localKey)
    {
        return new CustomMorphMany($query, $parent, $type, $id, $localKey);
    }

    protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey,
        $parentKey, $relatedKey, $relationName = null
    ) {
        return new CustomBelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }

    protected function newHasManyThrough(Builder $query, Model $farParent, Model $throughParent, $firstKey,
        $secondKey, $localKey, $secondLocalKey
    ) {
        return new CustomHasManyThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    protected function newHasOneThrough(Builder $query, Model $farParent, Model $throughParent, $firstKey,
        $secondKey, $localKey, $secondLocalKey
    ) {
        return new CustomHasOneThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
    }

    protected function newMorphToMany(Builder $query, Model $parent, $name, $table, $foreignPivotKey,
        $relatedPivotKey, $parentKey, $relatedKey, $relationName = null, $inverse = false)
    {
        return new CustomMorphToMany($query, $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey,
            $relationName, $inverse);
    }

    protected function newMorphTo(Builder $query, Model $parent, $foreignKey, $ownerKey, $type, $relation)
    {
        return new CustomMorphTo($query, $parent, $foreignKey, $ownerKey, $type, $relation);
    }
}

class CustomHasOne extends HasOne
{
    //
}

class CustomBelongsTo extends BelongsTo
{
    //
}

class CustomHasMany extends HasMany
{
    //
}

class CustomMorphOne extends MorphOne
{
    //
}

class CustomMorphMany extends MorphMany
{
    //
}

class CustomBelongsToMany extends BelongsToMany
{
    //
}

class CustomHasManyThrough extends HasManyThrough
{
    //
}

class CustomHasOneThrough extends HasOneThrough
{
    //
}

class CustomMorphToMany extends MorphToMany
{
    //
}

class CustomMorphTo extends MorphTo
{
    //
}

class MockedConnectionModel extends Model
{
    public function getConnection()
    {
        $mock = m::mock(Connection::class);
        $mock->shouldReceive('getQueryGrammar')->andReturn($grammar = m::mock(Grammar::class));
        $grammar->shouldReceive('getBitwiseOperators')->andReturn([]);
        $mock->shouldReceive('getPostProcessor')->andReturn($processor = m::mock(Processor::class));
        $mock->shouldReceive('getName')->andReturn('name');
        $mock->shouldReceive('query')->andReturnUsing(function () use ($mock, $grammar, $processor) {
            return new BaseBuilder($mock, $grammar, $processor);
        });

        return $mock;
    }
}

class Car extends MockedConnectionModel
{
    public function owner()
    {
        return $this->hasOne(Owner::class, 'car_id', 'c_id');
    }
}

class Owner extends MockedConnectionModel
{
    //
}

class FluentMechanic extends MockedConnectionModel
{
    public function owner()
    {
        return $this->through($this->car())
            ->has(fn (Car $car) => $car->owner());
    }

    public function car()
    {
        return $this->hasOne(Car::class, 'mechanic_id', 'm_id');
    }
}

class ClassicMechanic extends MockedConnectionModel
{
    public function owner()
    {
        return $this->hasOneThrough(Owner::class, Car::class, 'mechanic_id', 'car_id', 'm_id', 'c_id');
    }
}

class ClassicProject extends MockedConnectionModel
{
    public function deployments()
    {
        return $this->hasManyThrough(
            Deployment::class,
            Environment::class,
            'pro_id',
            'env_id',
            'p_id',
            'e_id',
        );
    }

    public function environmentData()
    {
        return $this->hasManyThrough(
            Metadata::class,
            Environment::class,
            'pro_id',
            'env_id',
            'p_id',
            'e_id',
        );
    }
}

class FluentProject extends MockedConnectionModel
{
    public function deployments()
    {
        return $this->through($this->environments())->has(fn (Environment $env) => $env->deployments());
    }

    public function environmentData()
    {
        return $this->through($this->environments())->has(fn (Environment $env) => $env->metadata());
    }

    public function environments()
    {
        return $this->hasMany(Environment::class, 'pro_id', 'p_id');
    }
}

class Environment extends MockedConnectionModel
{
    public function deployments()
    {
        return $this->hasMany(Deployment::class, 'env_id', 'e_id');
    }

    public function metadata()
    {
        return $this->hasOne(MetaData::class, 'env_id', 'e_id');
    }
}

class MetaData extends MockedConnectionModel
{
    //
}

class Deployment extends MockedConnectionModel
{
    //
}
