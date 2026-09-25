<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Tests\Database\Concerns\RestoresConnectionResolver;
use Illuminate\Tests\Database\Fixtures\TestEnum;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentMorphToTest extends TestCase
{
    use RestoresConnectionResolver;

    protected function setUp(): void
    {
        $this->useInMemoryConnection();
    }

    protected $builder;

    protected $related;

    public function testLookupDictionaryIsProperlyConstructedForEnums()
    {
        $relation = $this->getRelation();
        $relation->addEagerConstraints([
            $one = (object) ['morph_type' => 'morph_type_2', 'foreign_key' => TestEnum::test],
        ]);
        $dictionary = $relation->getDictionary();
        $relation->getDictionary();
        $enumKey = TestEnum::test;
        if (isset($enumKey->value)) {
            $value = $dictionary['morph_type_2'][$enumKey->value][0]->foreign_key;
            $this->assertEquals(TestEnum::test, $value);
        } else {
            $this->fail('An enum should contain value property');
        }
    }

    public function testLookupDictionaryIsProperlyConstructed()
    {
        $stringish = new class
        {
            public function __toString()
            {
                return 'foreign_key_2';
            }
        };

        $relation = $this->getRelation();
        $relation->addEagerConstraints([
            $one = (object) ['morph_type' => 'morph_type_1', 'foreign_key' => 'foreign_key_1'],
            $two = (object) ['morph_type' => 'morph_type_1', 'foreign_key' => 'foreign_key_1'],
            $three = (object) ['morph_type' => 'morph_type_2', 'foreign_key' => 'foreign_key_2'],
            $four = (object) ['morph_type' => 'morph_type_2', 'foreign_key' => $stringish],
        ]);

        $dictionary = $relation->getDictionary();

        $this->assertEquals([
            'morph_type_1' => [
                'foreign_key_1' => [
                    $one,
                    $two,
                ],
            ],
            'morph_type_2' => [
                'foreign_key_2' => [
                    $three,
                    $four,
                ],
            ],
        ], $dictionary);
    }

    public function testMorphToWithDefault()
    {
        $relation = $this->getRelation()->withDefault();

        $this->builder->expects('first')->andReturnNull();

        $newModel = new EloquentMorphToModelStub;

        $this->assertEquals($newModel, $relation->getResults());
    }

    public function testMorphToWithDynamicDefault()
    {
        $relation = $this->getRelation()->withDefault(function ($newModel) {
            $newModel->username = 'taylor';
        });

        $this->builder->expects('first')->andReturnNull();

        $newModel = new EloquentMorphToModelStub;
        $newModel->username = 'taylor';

        $result = $relation->getResults();

        $this->assertEquals($newModel, $result);

        $this->assertSame('taylor', $result->username);
    }

    public function testMorphToWithArrayDefault()
    {
        $relation = $this->getRelation()->withDefault(['username' => 'taylor']);

        $this->builder->expects('first')->andReturnNull();

        $newModel = new EloquentMorphToModelStub;
        $newModel->username = 'taylor';

        $result = $relation->getResults();

        $this->assertEquals($newModel, $result);

        $this->assertSame('taylor', $result->username);
    }

    public function testMorphToWithZeroMorphType(): void
    {
        $parent = $this->getMockBuilder(EloquentMorphToModelStub::class)->onlyMethods(['getAttributeFromArray', 'morphEagerTo', 'morphInstanceTo'])->getMock();
        $parent->expects($this->once())->method('getAttributeFromArray')->with('relation_type')->willReturn(0);
        $parent->expects($this->once())->method('morphInstanceTo');
        $parent->expects($this->never())->method('morphEagerTo');

        $parent->relation();
    }

    public function testMorphToWithEmptyStringMorphType(): void
    {
        $parent = $this->getMockBuilder(EloquentMorphToModelStub::class)->onlyMethods(['getAttributeFromArray', 'morphEagerTo', 'morphInstanceTo'])->getMock();
        $parent->expects($this->once())->method('getAttributeFromArray')->with('relation_type')->willReturn('');
        $parent->expects($this->once())->method('morphEagerTo');
        $parent->expects($this->never())->method('morphInstanceTo');

        $parent->relation();
    }

    public function testMorphToWithSpecifiedClassDefault()
    {
        $parent = new EloquentMorphToModelStub;
        $parent->relation_type = EloquentMorphToRelatedStub::class;

        $relation = $parent->relation()->withDefault();

        $newModel = new EloquentMorphToRelatedStub;

        $result = $relation->getResults();

        $this->assertEquals($newModel, $result);
    }

    public function testAssociateMethodSetsForeignKeyAndTypeOnModel()
    {
        $relation = $this->getRelationWithRealQuery();
        $associate = new EloquentMorphToRelatedStub;
        $associate->id = 1;

        $parent = $relation->associate($associate);

        $this->assertSame(1, $parent->getAttribute('foreign_key'));
        $this->assertSame(EloquentMorphToRelatedStub::class, $parent->getAttribute('morph_type'));
        $this->assertSame($associate, $parent->getRelation('relation'));
    }

    public function testAssociateMethodIgnoresNullValue()
    {
        $relation = $this->getRelationWithRealQuery();

        $parent = $relation->associate(null);

        $this->assertNull($parent->getAttribute('foreign_key'));
        $this->assertNull($parent->getAttribute('morph_type'));
        $this->assertNull($parent->getRelation('relation'));
    }

    public function testDissociateMethodDeletesUnsetsKeyAndTypeOnModel()
    {
        $relation = $this->getRelationWithRealQuery();
        $relation->getParent()->setAttribute('foreign_key', 5);
        $relation->getParent()->setAttribute('morph_type', 'type_1');

        $parent = $relation->dissociate();

        $this->assertNull($parent->getAttribute('foreign_key'));
        $this->assertNull($parent->getAttribute('morph_type'));
        $this->assertTrue($parent->relationLoaded('relation'));
        $this->assertNull($parent->getRelation('relation'));
    }

    public function testMatchToMorphParentsNormalizesKeyWhenOwnerKeyIsNullAndResultKeyIsObject()
    {
        $uuidObject = new class
        {
            public function __toString(): string
            {
                return 'uuid-value';
            }
        };

        $builder = Mockery::mock(Builder::class);
        $related = Mockery::mock(Model::class);
        $builder->expects('getModel')->andReturn($related);

        $parent = new EloquentMorphToModelStub;
        $parent->morph_type = 'type_1';
        $parent->foreign_key = 'uuid-value';

        $relation = Relation::noConstraints(function () use ($builder, $parent) {
            return new EloquentMorphToAccessibleStub($builder, $parent, 'foreign_key', null, 'morph_type', 'relation');
        });

        $relation->addEagerConstraints([$parent]);

        $result = Mockery::mock(Model::class);
        $result->expects('getKey')->andReturn($uuidObject);

        $relation->callMatchToMorphParents('type_1', new EloquentCollection([$result]));

        $this->assertSame($result, $parent->getRelation('relation'));
    }

    public function testCreateModelByTypeThrowsWhenTypeNotInMorphMapAndRequireMorphMapIsOn()
    {
        $this->expectException(ClassMorphViolationException::class);

        Relation::requireMorphMap();

        $this->getRelationWithRealQuery()->createModelByType('poisoned');
    }

    protected function tearDown(): void
    {
        Relation::morphMap([], false);
        Relation::requireMorphMap(false);
    }

    protected function getRelationWithRealQuery()
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $builder = (new Builder(new BaseBuilder($connection, new Grammar($connection), new Processor)))->setModel(new EloquentMorphToRelatedStub);

        return new MorphTo($builder, new EloquentMorphToModelStub, 'foreign_key', 'id', 'morph_type', 'relation');
    }

    public function getRelation($parent = null, $builder = null)
    {
        $this->builder = $builder ?: Mockery::mock(Builder::class);
        $this->builder->shouldReceive('where')->with('relation.id', '=', 'foreign.value');
        $this->related = Mockery::mock(Model::class);
        $this->related->shouldReceive('getKeyName')->andReturn('id');
        $this->related->shouldReceive('getTable')->andReturn('relation');
        $this->related->shouldReceive('qualifyColumn')->andReturnUsing(fn (string $column) => "relation.{$column}");
        $this->builder->shouldReceive('getModel')->andReturn($this->related);
        $parent = $parent ?: new EloquentMorphToModelStub;

        return Mockery::mock(MorphTo::class.'[createModelByType]', [$this->builder, $parent, 'foreign_key', 'id', 'morph_type', 'relation']);
    }
}

class EloquentMorphToModelStub extends Model
{
    public $foreign_key = 'foreign.value';

    public $table = 'eloquent_morph_to_model_stubs';

    public function relation()
    {
        return $this->morphTo();
    }
}

class EloquentMorphToRelatedStub extends Model
{
    public $table = 'eloquent_morph_to_related_stubs';
}

class EloquentMorphToAccessibleStub extends MorphTo
{
    public function callMatchToMorphParents($type, EloquentCollection $results): void
    {
        $this->matchToMorphParents($type, $results);
    }
}
