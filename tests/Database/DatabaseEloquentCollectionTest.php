<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection as BaseCollection;
use JMac\Testing\Double;
use LogicException;
use function Orchestra\Testbench\phpunit_version_compare;

use PHPUnit\Framework\TestCase;

class DatabaseEloquentCollectionTest extends TestCase
{
    /**
     * Setup the database schema.
     *
     * @return void
     */
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
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
        });

        $this->schema()->create('articles', function ($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('title');
        });

        $this->schema()->create('comments', function ($table) {
            $table->increments('id');
            $table->integer('article_id');
            $table->string('content');
        });
    }

    protected function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop('articles');
        $this->schema()->drop('comments');
    }

    public function testAddingItemsToCollection()
    {
        $c = new Collection(['foo']);
        $c->add('bar')->add('baz');
        $this->assertEquals(['foo', 'bar', 'baz'], $c->all());
    }

    public function testGettingMaxItemsFromCollection()
    {
        $c = new Collection([(object) ['foo' => 10], (object) ['foo' => 20]]);
        $this->assertEquals(20, $c->max('foo'));
    }

    public function testGettingMinItemsFromCollection()
    {
        $c = new Collection([(object) ['foo' => 10], (object) ['foo' => 20]]);
        $this->assertEquals(10, $c->min('foo'));
    }

    public function testContainsWithMultipleArguments()
    {
        $c = new Collection([['id' => 1], ['id' => 2]]);

        $this->assertTrue($c->contains('id', 1));
        $this->assertTrue($c->contains('id', '>=', 2));
        $this->assertFalse($c->contains('id', '>', 2));

        $this->assertFalse($c->doesntContain('id', 1));
        $this->assertFalse($c->doesntContain('id', '>=', 2));
        $this->assertTrue($c->doesntContain('id', '>', 2));
    }

    public function testContainsIndicatesIfModelInArray()
    {
        $mockModel = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);
        $mockModel2 = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);
        $mockModel3 = (new TestEloquentCollectionModel)->forceFill(['id' => 3]);
        $c = new Collection([$mockModel, $mockModel2]);

        $this->assertTrue($c->contains($mockModel));
        $this->assertTrue($c->contains($mockModel2));
        $this->assertFalse($c->contains($mockModel3));

        $this->assertFalse($c->doesntContain($mockModel));
        $this->assertFalse($c->doesntContain($mockModel2));
        $this->assertTrue($c->doesntContain($mockModel3));
    }

    public function testContainsIndicatesIfDifferentModelInArray()
    {
        $mockModelFoo = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);
        $mockModelBar = (new EloquentTestUserModel)->forceFill(['id' => 1]);
        $c = new Collection([$mockModelFoo]);

        $this->assertTrue($c->contains($mockModelFoo));
        $this->assertFalse($c->contains($mockModelBar));

        $this->assertFalse($c->doesntContain($mockModelFoo));
        $this->assertTrue($c->doesntContain($mockModelBar));
    }

    public function testContainsIndicatesIfKeyedModelInArray()
    {
        $mockModel = (new TestEloquentCollectionModel)->forceFill(['id' => '1']);
        $c = new Collection([$mockModel]);
        $mockModel2 = (new TestEloquentCollectionModel)->forceFill(['id' => '2']);
        $c->add($mockModel2);

        $this->assertTrue($c->contains(1));
        $this->assertTrue($c->contains(2));
        $this->assertFalse($c->contains(3));

        $this->assertFalse($c->doesntContain(1));
        $this->assertFalse($c->doesntContain(2));
        $this->assertTrue($c->doesntContain(3));
    }

    public function testContainsKeyAndValueIndicatesIfModelInArray()
    {
        $mockModel1 = (new TestEloquentCollectionModel)->forceFill(['name' => 'Taylor']);
        $mockModel2 = (new TestEloquentCollectionModel)->forceFill(['name' => 'Abigail']);
        $c = new Collection([$mockModel1, $mockModel2]);

        $this->assertTrue($c->contains('name', 'Taylor'));
        $this->assertTrue($c->contains('name', 'Abigail'));
        $this->assertFalse($c->contains('name', 'Dayle'));

        $this->assertFalse($c->doesntContain('name', 'Taylor'));
        $this->assertFalse($c->doesntContain('name', 'Abigail'));
        $this->assertTrue($c->doesntContain('name', 'Dayle'));
    }

    public function testContainsClosureIndicatesIfModelInArray()
    {
        $mockModel1 = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);
        $mockModel2 = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);
        $c = new Collection([$mockModel1, $mockModel2]);

        $this->assertTrue($c->contains(function ($model) {
            return $model->getKey() < 2;
        }));
        $this->assertFalse($c->contains(function ($model) {
            return $model->getKey() > 2;
        }));

        $this->assertFalse($c->doesntContain(function ($model) {
            return $model->getKey() < 2;
        }));
        $this->assertTrue($c->doesntContain(function ($model) {
            return $model->getKey() > 2;
        }));
    }

    public function testFindMethodFindsModelById()
    {
        $mockModel = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);
        $c = new Collection([$mockModel]);

        $this->assertSame($mockModel, $c->find(1));
        $this->assertSame('taylor', $c->find(2, 'taylor'));
    }

    public function testFindMethodFindsManyModelsById()
    {
        $model1 = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);
        $model2 = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);
        $model3 = (new TestEloquentCollectionModel)->forceFill(['id' => 3]);

        $c = new Collection;
        $this->assertInstanceOf(Collection::class, $c->find([]));
        $this->assertCount(0, $c->find([1]));

        $c->push($model1);
        $this->assertCount(1, $c->find([1]));
        $this->assertEquals(1, $c->find([1])->first()->id);
        $this->assertCount(0, $c->find([2]));

        $c->push($model2)->push($model3);
        $this->assertCount(1, $c->find([2]));
        $this->assertEquals(2, $c->find([2])->first()->id);
        $this->assertCount(2, $c->find([2, 3, 4]));
        $this->assertCount(2, $c->find(collect([2, 3, 4])));
        $this->assertEquals([2, 3], $c->find(collect([2, 3, 4]))->pluck('id')->all());
        $this->assertEquals([2, 3], $c->find([2, 3, 4])->pluck('id')->all());
    }

    public function testFindOrFailFindsModelById()
    {
        $mockModel = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);
        $c = new Collection([$mockModel]);

        $this->assertSame($mockModel, $c->findOrFail(1));
    }

    public function testFindOrFailFindsManyModelsById()
    {
        $model1 = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);
        $model2 = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);

        $c = new Collection;
        $this->assertInstanceOf(Collection::class, $c->findOrFail([]));
        $this->assertCount(0, $c->findOrFail([]));

        $c->push($model1);
        $this->assertCount(1, $c->findOrFail([1]));
        $this->assertEquals(1, $c->findOrFail([1])->first()->id);

        $c->push($model2);
        $this->assertCount(2, $c->findOrFail([1, 2]));

        $this->expectExceptionObject(new ModelNotFoundException('No query results for model [Illuminate\Tests\Database\TestEloquentCollectionModel] 3'));

        $c->findOrFail([1, 2, 3]);
    }

    public function testFindOrFailFindsManyModelsByArrayableIds()
    {
        $model1 = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);
        $model2 = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);

        $c = new Collection([$model1, $model2]);

        $this->assertCount(2, $c->findOrFail(new BaseCollection([1, 2])));

        $this->expectExceptionObject(new ModelNotFoundException('No query results for model [Illuminate\Tests\Database\TestEloquentCollectionModel] 3'));

        $c->findOrFail(new BaseCollection([1, 3]));
    }

    public function testFindOrFailThrowsExceptionWithMessageWhenOtherModelsArePresent()
    {
        $model = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);

        $c = new Collection([$model]);

        $this->expectExceptionObject(new ModelNotFoundException('No query results for model [Illuminate\Tests\Database\TestEloquentCollectionModel] 2'));

        $c->findOrFail(2);
    }

    public function testFindOrFailThrowsExceptionWithoutMessageWhenOtherModelsAreNotPresent()
    {
        $c = new Collection();

        $this->expectExceptionObject(new ModelNotFoundException(''));

        $c->findOrFail(1);
    }

    public function testLoadMethodEagerLoadsGivenRelationships()
    {
        $model = Double::for(Model::class);
        $builder = Double::for(Builder::class);
        $model->expects('newQueryWithoutRelationships')->returns($builder);
        $builder->expects('with')->with(['bar', 'baz'])->returns($builder);
        $builder->expects('eagerLoadRelations')->with([$model])->returns(['results']);
        $c = new Collection([$model]);
        $c->load('bar', 'baz');

        $this->assertEquals(['results'], $c->all());
    }

    public function testLoadMissingWithoutRelationsDoesNotBuildAQuery()
    {
        $model = Double::for(Model::class);
        $model->expects('newQueryWithoutRelationships')->never();
        $c = new Collection([$model]);

        $this->assertSame($c, $c->loadMissing([]));
    }

    public function testCollectionDictionaryReturnsModelKeys()
    {
        $one = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);

        $two = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);

        $three = (new TestEloquentCollectionModel)->forceFill(['id' => 3]);

        $c = new Collection([$one, $two, $three]);

        $this->assertEquals([1, 2, 3], $c->modelKeys());
    }

    public function testCollectionMergesWithGivenCollection()
    {
        $one = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);

        $two = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);

        $three = (new TestEloquentCollectionModel)->forceFill(['id' => 3]);

        $c1 = new Collection([$one, $two]);
        $c2 = new Collection([$two, $three]);

        $this->assertEquals(new Collection([$one, $two, $three]), $c1->merge($c2));
    }

    public function testMap()
    {
        $one = new TestEloquentCollectionModel;
        $two = new TestEloquentCollectionModel;

        $c = new Collection([$one, $two]);

        $cAfterMap = $c->map(function ($item) {
            return $item;
        });

        $this->assertEquals($c->all(), $cAfterMap->all());
        $this->assertInstanceOf(Collection::class, $cAfterMap);
    }

    public function testMappingToNonModelsReturnsABaseCollection()
    {
        $one = new TestEloquentCollectionModel;
        $two = new TestEloquentCollectionModel;

        $c = (new Collection([$one, $two]))->map(function ($item) {
            return 'not-a-model';
        });

        $this->assertInstanceOf(BaseCollection::class, $c);
    }

    public function testMapWithKeys()
    {
        $one = new TestEloquentCollectionModel;
        $two = new TestEloquentCollectionModel;

        $c = new Collection([$one, $two]);

        $key = 0;
        $cAfterMap = $c->mapWithKeys(function ($item) use (&$key) {
            return [$key++ => $item];
        });

        $this->assertEquals($c->all(), $cAfterMap->all());
        $this->assertInstanceOf(Collection::class, $cAfterMap);
    }

    public function testMapWithKeysToNonModelsReturnsABaseCollection()
    {
        $one = new TestEloquentCollectionModel;
        $two = new TestEloquentCollectionModel;

        $key = 0;
        $c = (new Collection([$one, $two]))->mapWithKeys(function ($item) use (&$key) {
            return [$key++ => 'not-a-model'];
        });

        $this->assertInstanceOf(BaseCollection::class, $c);
    }

    public function testCollectionDiffsWithGivenCollection()
    {
        $one = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);

        $two = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);

        $three = (new TestEloquentCollectionModel)->forceFill(['id' => 3]);

        $c1 = new Collection([$one, $two]);
        $c2 = new Collection([$two, $three]);

        $this->assertEquals(new Collection([$one]), $c1->diff($c2));
    }

    public function testCollectionReturnsDuplicateBasedOnlyOnKeys()
    {
        $one = new TestEloquentCollectionModel;
        $two = new TestEloquentCollectionModel;
        $three = new TestEloquentCollectionModel;
        $four = new TestEloquentCollectionModel;
        $one->id = 1;
        $one->someAttribute = '1';
        $two->id = 1;
        $two->someAttribute = '2';
        $three->id = 1;
        $three->someAttribute = '3';
        $four->id = 2;
        $four->someAttribute = '4';

        $duplicates = Collection::make([$one, $two, $three, $four])->duplicates()->all();
        $this->assertSame([1 => $two, 2 => $three], $duplicates);

        $duplicates = Collection::make([$one, $two, $three, $four])->duplicatesStrict()->all();
        $this->assertSame([1 => $two, 2 => $three], $duplicates);
    }

    public function testCollectionDuplicatesWithKey()
    {
        $one = new TestEloquentCollectionModel;
        $two = new TestEloquentCollectionModel;
        $three = new TestEloquentCollectionModel;

        $one->someAttribute = '1';
        $two->someAttribute = '2';
        $three->someAttribute = '1';

        $duplicates = Collection::make([$one, $two, $three])->duplicates('someAttribute')->all();
        $this->assertSame([2 => '1'], $duplicates);

        $duplicates = Collection::make([$one, $two, $three])->duplicatesStrict('someAttribute')->all();
        $this->assertSame([2 => '1'], $duplicates);
    }

    public function testCollectionIntersectWithNull()
    {
        $one = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);

        $two = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);

        $three = (new TestEloquentCollectionModel)->forceFill(['id' => 3]);

        $c1 = new Collection([$one, $two, $three]);

        $this->assertSame([], $c1->intersect(null)->all());
    }

    public function testCollectionIntersectsWithGivenCollection()
    {
        $one = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);

        $two = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);

        $three = (new TestEloquentCollectionModel)->forceFill(['id' => 3]);

        $c1 = new Collection([$one, $two]);
        $c2 = new Collection([$two, $three]);

        $this->assertEquals(new Collection([$two]), $c1->intersect($c2));
    }

    public function testCollectionReturnsUniqueItems()
    {
        $one = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);

        $two = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);

        $c = new Collection([$one, $two, $two]);

        $this->assertEquals(new Collection([$one, $two]), $c->unique());
    }

    public function testCollectionReturnsUniqueStrictBasedOnKeysOnly()
    {
        $one = new TestEloquentCollectionModel;
        $two = new TestEloquentCollectionModel;
        $three = new TestEloquentCollectionModel;
        $four = new TestEloquentCollectionModel;
        $one->id = 1;
        $one->someAttribute = '1';
        $two->id = 1;
        $two->someAttribute = '2';
        $three->id = 1;
        $three->someAttribute = '3';
        $four->id = 2;
        $four->someAttribute = '4';

        $uniques = Collection::make([$one, $two, $three, $four])->unique()->all();
        $this->assertSame([$three, $four], $uniques);

        $uniques = Collection::make([$one, $two, $three, $four])->unique(null, true)->all();
        $this->assertSame([$three, $four], $uniques);
    }

    public function testOnlyReturnsCollectionWithGivenModelKeys()
    {
        $one = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);

        $two = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);

        $three = (new TestEloquentCollectionModel)->forceFill(['id' => 3]);

        $c = new Collection([$one, $two, $three]);

        $this->assertEquals($c, $c->only(null));
        $this->assertEquals(new Collection([$one]), $c->only(1));
        $this->assertEquals(new Collection([$two, $three]), $c->only([2, 3]));
    }

    public function testExceptReturnsCollectionWithoutGivenModelKeys()
    {
        $one = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);

        $two = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);

        $three = (new TestEloquentCollectionModel)->forceFill(['id' => 3]);

        $c = new Collection([$one, $two, $three]);

        $this->assertEquals($c, $c->except(null));
        $this->assertEquals(new Collection([$one, $three]), $c->except(2));
        $this->assertEquals(new Collection([$one]), $c->except([2, 3]));
    }

    public function testMakeHiddenAddsHiddenOnEntireCollection()
    {
        $c = new Collection([new TestEloquentCollectionModel]);
        $c = $c->makeHidden(['visible']);

        $this->assertEquals(['hidden', 'visible'], $c[0]->getHidden());
    }

    public function testMakeVisibleRemovesHiddenFromEntireCollection()
    {
        $c = new Collection([new TestEloquentCollectionModel]);
        $c = $c->makeVisible(['hidden']);

        $this->assertSame([], $c[0]->getHidden());
    }

    public function testMergeHiddenAddsHiddenOnEntireCollection()
    {
        $c = new Collection([new TestEloquentCollectionModel]);
        $c = $c->mergeHidden(['merged']);

        $this->assertEquals(['hidden', 'merged'], $c[0]->getHidden());
    }

    public function testMergeVisibleRemovesHiddenFromEntireCollection()
    {
        $c = new Collection([new TestEloquentCollectionModel]);
        $c = $c->mergeVisible(['merged']);

        $this->assertEquals(['visible', 'merged'], $c[0]->getVisible());
    }

    public function testSetVisibleReplacesVisibleOnEntireCollection()
    {
        $c = new Collection([new TestEloquentCollectionModel]);
        $c = $c->setVisible(['hidden']);

        $this->assertEquals(['hidden'], $c[0]->getVisible());
    }

    public function testSetHiddenReplacesHiddenOnEntireCollection()
    {
        $c = new Collection([new TestEloquentCollectionModel]);
        $c = $c->setHidden(['visible']);

        $this->assertEquals(['visible'], $c[0]->getHidden());
    }

    public function testAppendsAddsTestOnEntireCollection()
    {
        $c = new Collection([new TestEloquentCollectionModel]);
        $c = $c->makeVisible('test');
        $c = $c->append('test');

        $this->assertEquals(['test' => 'test'], $c[0]->toArray());
    }

    public function testSetAppendsSetsAppendedPropertiesOnEntireCollection()
    {
        $c = new Collection([new EloquentAppendingTestUserModel]);
        $c->setAppends(['other_appended_field']);

        $this->assertEquals(
            [['other_appended_field' => 'bye']],
            $c->toArray()
        );
    }

    public function testWithoutAppendsRemovesAppendsOnEntireCollection()
    {
        $this->seedData();
        $c = EloquentAppendingTestUserModel::query()->get();
        $this->assertSame('hello', $c->toArray()[0]['appended_field']);

        $c = $c->withoutAppends();
        $this->assertArrayNotHasKey('appended_field', $c->toArray()[0]);
    }

    public function testNonModelRelatedMethods()
    {
        $a = new Collection([['foo' => 'bar'], ['foo' => 'baz']]);
        $b = new Collection(['a', 'b', 'c']);
        $this->assertInstanceOf(BaseCollection::class, $a->pluck('foo'));
        $this->assertInstanceOf(BaseCollection::class, $a->keys());
        $this->assertInstanceOf(BaseCollection::class, $a->collapse());
        $this->assertInstanceOf(BaseCollection::class, $a->flatten());
        $this->assertInstanceOf(BaseCollection::class, $a->zip(['a', 'b'], ['c', 'd']));
        $this->assertInstanceOf(BaseCollection::class, $a->countBy('foo'));
        $this->assertInstanceOf(BaseCollection::class, $b->flip());
        $this->assertInstanceOf(BaseCollection::class, $a->partition('foo', '=', 'bar'));
        $this->assertInstanceOf(BaseCollection::class, $a->partition('foo', 'bar'));
    }

    public function testMakeVisibleRemovesHiddenAndIncludesVisible()
    {
        $c = new Collection([new TestEloquentCollectionModel]);
        $c = $c->makeVisible('hidden');

        $this->assertSame([], $c[0]->getHidden());
        $this->assertEquals(['visible', 'hidden'], $c[0]->getVisible());
    }

    public function testMultiply()
    {
        $a = new TestEloquentCollectionModel();
        $b = new TestEloquentCollectionModel();

        $c = new Collection([$a, $b]);

        $this->assertSame([], $c->multiply(-1)->all());
        $this->assertSame([], $c->multiply(0)->all());

        $this->assertEquals([$a, $b], $c->multiply(1)->all());

        $this->assertEquals([$a, $b, $a, $b, $a, $b], $c->multiply(3)->all());
    }

    public function testQueueableCollectionImplementation()
    {
        $c = new Collection([new TestEloquentCollectionModel, new TestEloquentCollectionModel]);
        $this->assertEquals(TestEloquentCollectionModel::class, $c->getQueueableClass());
    }

    public function testQueueableCollectionImplementationThrowsExceptionOnMultipleModelTypes()
    {
        $this->expectExceptionObject(new LogicException('Queueing collections with multiple model types is not supported.'));

        $c = new Collection([new TestEloquentCollectionModel, (object) ['id' => 'something']]);
        $c->getQueueableClass();
    }

    public function testQueueableRelationshipsReturnsOnlyRelationsCommonToAllModels()
    {
        // This is needed to prevent loading non-existing relationships on polymorphic model collections (#26126)
        $c = new Collection([
            new class
            {
                public function getQueueableRelations()
                {
                    return ['user'];
                }
            },
            new class
            {
                public function getQueueableRelations()
                {
                    return ['user', 'comments'];
                }
            },
        ]);

        $this->assertEquals(['user'], $c->getQueueableRelations());
    }

    public function testQueueableRelationshipsIgnoreCollectionKeys()
    {
        $c = new Collection([
            'foo' => new class
            {
                public function getQueueableRelations()
                {
                    return [];
                }
            },
            'bar' => new class
            {
                public function getQueueableRelations()
                {
                    return [];
                }
            },
        ]);

        $this->assertSame([], $c->getQueueableRelations());
    }

    public function testEmptyCollectionStayEmptyOnFresh()
    {
        $c = new Collection;
        $this->assertEquals($c, $c->fresh());
    }

    public function testCanConvertCollectionOfModelsToEloquentQueryBuilder()
    {
        $one = Double::for(Model::class);
        $one->allows('getKey')->returns(1);

        $two = Double::for(Model::class);
        $two->allows('getKey')->returns(2);

        $c = new Collection([$one, $two]);

        $mocBuilder = Double::for(Builder::class);
        $one->expects('newModelQuery')->returns($mocBuilder);
        $mocBuilder->expects('whereKey')->with($c->modelKeys())->returns($mocBuilder);
        $this->assertInstanceOf(Builder::class, $c->toQuery());
    }

    public function testConvertingEmptyCollectionToQueryThrowsException()
    {
        $this->expectException(LogicException::class);

        $c = new Collection;
        $c->toQuery();
    }

    public function testLoadExistsShouldCastBool()
    {
        $this->seedData();
        $user = EloquentTestUserModel::with('articles')->first();
        $user->articles->loadExists('comments');
        $commentsExists = $user->articles->pluck('comments_exists')->toArray();

        if (phpunit_version_compare('11.5.0', '<')) {
            $this->assertContainsOnly('bool', $commentsExists);
        } else {
            $this->assertContainsOnlyBool($commentsExists);
        }
    }

    public function testWithNonScalarKey()
    {
        $fooKey = new EloquentTestKey('foo');
        $foo = (new EloquentTestNonIncrementingModel)->forceFill(['id' => $fooKey]);

        $barKey = new EloquentTestKey('bar');
        $bar = (new EloquentTestNonIncrementingModel)->forceFill(['id' => $barKey]);

        $collection = new Collection([$foo, $bar]);

        $this->assertCount(1, $collection->only([$fooKey]));
        $this->assertSame($foo, $collection->only($fooKey)->first());

        $this->assertCount(1, $collection->except([$fooKey]));
        $this->assertSame($bar, $collection->except($fooKey)->first());
    }

    public function testPluck()
    {
        $model1 = (new TestEloquentCollectionModel)->forceFill(['id' => 1, 'name' => 'John', 'country' => 'US']);
        $model2 = (new TestEloquentCollectionModel)->forceFill(['id' => 2, 'name' => 'Jane', 'country' => 'NL']);
        $model3 = (new TestEloquentCollectionModel)->forceFill(['id' => 3, 'name' => 'Taylor', 'country' => 'US']);

        $c = new Collection;

        $c->push($model1)->push($model2)->push($model3);

        $this->assertInstanceOf(BaseCollection::class, $c->pluck('id'));
        $this->assertEquals([1, 2, 3], $c->pluck('id')->all());

        $this->assertInstanceOf(BaseCollection::class, $c->pluck('id', 'id'));
        $this->assertEquals([1 => 1, 2 => 2, 3 => 3], $c->pluck('id', 'id')->all());
        $this->assertInstanceOf(BaseCollection::class, $c->pluck('test'));

        $this->assertEquals(['John (US)', 'Jane (NL)', 'Taylor (US)'], $c->pluck(fn (TestEloquentCollectionModel $model) => "{$model->name} ({$model->country})")->all());
    }

    /**
     * Helpers...
     */
    protected function seedData()
    {
        $user = EloquentTestUserModel::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);

        EloquentTestArticleModel::query()->insert([
            ['user_id' => 1, 'title' => 'Another title'],
            ['user_id' => 1, 'title' => 'Another title'],
            ['user_id' => 1, 'title' => 'Another title'],
        ]);

        EloquentTestCommentModel::query()->insert([
            ['article_id' => 1, 'content' => 'Another comment'],
            ['article_id' => 2, 'content' => 'Another comment'],
        ]);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

class TestEloquentCollectionModel extends Model
{
    protected $visible = ['visible'];
    protected $hidden = ['hidden'];

    public function getTestAttribute()
    {
        return 'test';
    }
}

class EloquentTestNonIncrementingModel extends Model
{
    public $incrementing = false;
}

class EloquentTestUserModel extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;

    public function articles()
    {
        return $this->hasMany(EloquentTestArticleModel::class, 'user_id');
    }
}

class EloquentTestArticleModel extends Model
{
    protected $table = 'articles';
    protected $guarded = [];
    public $timestamps = false;

    public function comments()
    {
        return $this->hasMany(EloquentTestCommentModel::class, 'article_id');
    }
}

class EloquentTestCommentModel extends Model
{
    protected $table = 'comments';
    protected $guarded = [];
    public $timestamps = false;
}

class EloquentTestKey
{
    public function __construct(private readonly string $key)
    {
    }

    public function __toString()
    {
        return $this->key;
    }
}

class EloquentAppendingTestUserModel extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
    protected $appends = ['appended_field'];

    public function getAppendedFieldAttribute()
    {
        return 'hello';
    }

    public function getOtherAppendedFieldAttribute()
    {
        return 'bye';
    }

    public function articles()
    {
        return $this->hasMany(EloquentTestArticleModel::class, 'user_id');
    }
}
