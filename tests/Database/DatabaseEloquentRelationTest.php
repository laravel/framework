<?php

namespace Illuminate\Tests\Database;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Carbon;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentRelationTest extends TestCase
{
    public function testSetRelationFail()
    {
        $parent = new EloquentRelationResetModelStub;
        $relation = new EloquentRelationResetModelStub;
        $parent->setRelation('test', $relation);
        $parent->setRelation('foo', 'bar');
        $this->assertArrayNotHasKey('foo', $parent->toArray());
    }

    public function testUnsetExistingRelation()
    {
        $parent = new EloquentRelationResetModelStub;
        $relation = new EloquentRelationResetModelStub;
        $parent->setRelation('foo', $relation);
        $parent->unsetRelation('foo');
        $this->assertFalse($parent->relationLoaded('foo'));
    }

    public function testTouchMethodUpdatesRelatedTimestamps()
    {
        $connection = $this->newConnection();
        $relation = $this->newHasOne($connection, new EloquentNoTouchingModelStub, new EloquentNoTouchingModelStub);

        $relation->touch();

        $this->assertSame('2023-01-01 00:00:00', $this->updatedAt($connection, 'table'));
    }

    public function testCanDisableParentTouchingForAllModels()
    {
        $connection = $this->newConnection();
        $related = new EloquentNoTouchingModelStub;

        $this->assertFalse($related::isIgnoringTouch());

        Model::withoutTouching(function () use ($connection, $related) {
            $this->assertTrue($related::isIgnoringTouch());

            $this->newHasOne($connection, $related, new EloquentNoTouchingModelStub)->touch();
        });

        $this->assertNull($this->updatedAt($connection, 'table'));
        $this->assertFalse($related::isIgnoringTouch());
    }

    public function testCanDisableTouchingForSpecificModel()
    {
        $connection = $this->newConnection();
        $related = new EloquentNoTouchingModelStub;
        $anotherRelated = new EloquentNoTouchingAnotherModelStub;

        $this->assertFalse($related::isIgnoringTouch());
        $this->assertFalse($anotherRelated::isIgnoringTouch());

        EloquentNoTouchingModelStub::withoutTouching(function () use ($connection, $related, $anotherRelated) {
            $this->assertTrue($related::isIgnoringTouch());
            $this->assertFalse($anotherRelated::isIgnoringTouch());

            $this->newHasOne($connection, $related, new EloquentNoTouchingModelStub)->touch();
            $this->newHasOne($connection, $anotherRelated, new EloquentNoTouchingAnotherModelStub)->touch();
        });

        $this->assertNull($this->updatedAt($connection, 'table'));
        $this->assertSame('2023-01-01 00:00:00', $this->updatedAt($connection, 'another_table'));
        $this->assertFalse($related::isIgnoringTouch());
        $this->assertFalse($anotherRelated::isIgnoringTouch());
    }

    public function testParentModelIsNotTouchedWhenChildModelIsIgnored()
    {
        $connection = $this->newConnection();
        $related = new EloquentNoTouchingModelStub;
        $relatedChild = new EloquentNoTouchingChildModelStub;

        $this->assertFalse($related::isIgnoringTouch());
        $this->assertFalse($relatedChild::isIgnoringTouch());

        EloquentNoTouchingModelStub::withoutTouching(function () use ($connection, $related, $relatedChild) {
            $this->assertTrue($related::isIgnoringTouch());
            $this->assertTrue($relatedChild::isIgnoringTouch());

            $this->newHasOne($connection, $related, new EloquentNoTouchingModelStub)->touch();
            $this->newHasOne($connection, $relatedChild, new EloquentNoTouchingChildModelStub)->touch();
        });

        $this->assertNull($this->updatedAt($connection, 'table'));
        $this->assertFalse($related::isIgnoringTouch());
        $this->assertFalse($relatedChild::isIgnoringTouch());
    }

    public function testIgnoredModelsStateIsResetWhenThereAreExceptions()
    {
        $related = new EloquentNoTouchingModelStub;

        $relatedChild = new EloquentNoTouchingChildModelStub;

        $this->assertFalse($related::isIgnoringTouch());
        $this->assertFalse($relatedChild::isIgnoringTouch());

        try {
            EloquentNoTouchingModelStub::withoutTouching(function () use ($related, $relatedChild) {
                $this->assertTrue($related::isIgnoringTouch());
                $this->assertTrue($relatedChild::isIgnoringTouch());

                throw new Exception;
            });

            $this->fail('Exception was not thrown');
        } catch (Exception) {
            // Does nothing.
        }

        $this->assertFalse($related::isIgnoringTouch());
        $this->assertFalse($relatedChild::isIgnoringTouch());
    }

    public function testSettingMorphMapWithNumericArrayUsesTheTableNames()
    {
        Relation::morphMap([EloquentRelationResetModelStub::class]);

        $this->assertEquals([
            'reset' => EloquentRelationResetModelStub::class,
        ], Relation::morphMap());

        Relation::morphMap([], false);
    }

    public function testSettingMorphMapWithNumericKeys()
    {
        Relation::morphMap([1 => 'App\User']);

        $this->assertEquals([
            1 => 'App\User',
        ], Relation::morphMap());

        Relation::morphMap([], false);
    }

    public function testGetMorphedModel()
    {
        Relation::morphMap(['user' => 'App\User', 1 => 'App\Team']);

        $this->assertSame('App\User', Relation::getMorphedModel('user'));
        $this->assertSame('App\Team', Relation::getMorphedModel(1));
        $this->assertNull(Relation::getMorphedModel('does_not_exist'));
        $this->assertNull(Relation::getMorphedModel(null));

        Relation::morphMap([], false);
    }

    public function testGetMorphAlias()
    {
        Relation::morphMap(['user' => 'App\User']);

        $this->assertSame('user', Relation::getMorphAlias('App\User'));
        $this->assertSame('Does\Not\Exist', Relation::getMorphAlias('Does\Not\Exist'));
    }

    public function testWithoutRelations()
    {
        $original = new EloquentNoTouchingModelStub;

        $original->setRelation('foo', 'baz');

        $this->assertSame('baz', $original->getRelation('foo'));

        $model = $original->withoutRelations();

        $this->assertInstanceOf(EloquentNoTouchingModelStub::class, $model);
        $this->assertTrue($original->relationLoaded('foo'));
        $this->assertFalse($model->relationLoaded('foo'));

        $model = $original->unsetRelations();

        $this->assertInstanceOf(EloquentNoTouchingModelStub::class, $model);
        $this->assertFalse($original->relationLoaded('foo'));
        $this->assertFalse($model->relationLoaded('foo'));
    }

    public function testWithoutRelation()
    {
        $original = new EloquentNoTouchingModelStub;

        $original->setRelation('foo', 'baz');
        $original->setRelation('bar', 'qux');

        $model = $original->withoutRelation('foo');

        $this->assertInstanceOf(EloquentNoTouchingModelStub::class, $model);
        $this->assertNotSame($model, $original);
        $this->assertTrue($original->relationLoaded('foo'));
        $this->assertTrue($original->relationLoaded('bar'));
        $this->assertFalse($model->relationLoaded('foo'));
        $this->assertTrue($model->relationLoaded('bar'));
    }

    public function testWithoutRelationWithArray()
    {
        $original = new EloquentNoTouchingModelStub;

        $original->setRelation('foo', 'baz');
        $original->setRelation('bar', 'qux');
        $original->setRelation('bam', 'zap');

        $model = $original->withoutRelation(['foo', 'bar']);

        $this->assertTrue($original->relationLoaded('foo'));
        $this->assertTrue($original->relationLoaded('bar'));
        $this->assertTrue($original->relationLoaded('bam'));
        $this->assertFalse($model->relationLoaded('foo'));
        $this->assertFalse($model->relationLoaded('bar'));
        $this->assertTrue($model->relationLoaded('bam'));
    }

    public function testMacroable()
    {
        Relation::macro('foo', function () {
            return 'foo';
        });

        $model = new EloquentRelationResetModelStub;
        $builder = (new Builder($this->newConnection()->query()))->setModel($model);
        $relation = new EloquentRelationStub($builder, $model);

        $result = $relation->foo();
        $this->assertSame('foo', $result);
    }

    public function testIsRelationIgnoresAttribute()
    {
        $model = new EloquentRelationAndAttributeModelStub;

        $this->assertTrue($model->isRelation('parent'));
        $this->assertFalse($model->isRelation('field'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Relation::morphMap([], false);

        parent::tearDown();
    }

    protected function newConnection(): SQLiteConnection
    {
        Carbon::setTestNow('2023-01-01 00:00:00');

        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('create table "table" ("id" integer primary key, "foreign_key" integer, "updated_at" text)');
        $pdo->exec('create table "another_table" ("id" integer primary key, "foreign_key" integer, "updated_at" text)');
        $pdo->exec('insert into "table" ("id", "foreign_key") values (1, 1)');
        $pdo->exec('insert into "another_table" ("id", "foreign_key") values (1, 2)');

        return new SQLiteConnection($pdo);
    }

    protected function newHasOne(SQLiteConnection $connection, Model $related, Model $parent): HasOne
    {
        $builder = (new Builder($connection->query()))->setModel($related);

        return new HasOne($builder, $parent, 'foreign_key', 'id');
    }

    protected function updatedAt(SQLiteConnection $connection, string $table): ?string
    {
        return $connection->scalar('select "updated_at" from "'.$table.'"');
    }
}

class EloquentRelationResetModelStub extends Model
{
    protected $table = 'reset';

    // Override method call which would normally go through __call()

    public function getQuery()
    {
        return $this->newQuery()->getQuery();
    }
}

class EloquentRelationStub extends Relation
{
    public function addConstraints()
    {
        //
    }

    public function addEagerConstraints(array $models)
    {
        //
    }

    public function initRelation(array $models, $relation)
    {
        //
    }

    public function match(array $models, Collection $results, $relation)
    {
        //
    }

    public function getResults()
    {
        //
    }
}

class EloquentNoTouchingModelStub extends Model
{
    protected $table = 'table';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $attributes = [
        'id' => 1,
    ];
}

class EloquentNoTouchingChildModelStub extends EloquentNoTouchingModelStub
{
    //
}

class EloquentNoTouchingAnotherModelStub extends Model
{
    protected $table = 'another_table';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $attributes = [
        'id' => 2,
    ];
}

class EloquentRelationAndAttributeModelStub extends Model
{
    protected $table = 'one_more_table';

    public function field(): Attribute
    {
        return new Attribute(
            function ($value) {
                return $value;
            },
            function ($value) {
                return $value;
            },
        );
    }

    public function parent()
    {
        return $this->belongsTo(self::class);
    }
}
