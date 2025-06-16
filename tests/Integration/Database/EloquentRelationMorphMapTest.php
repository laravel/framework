<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;

class EloquentRelationMorphMapTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([], false);
    }

    #[TestWith([MorphMap::class])]
    public function testMorphMapEnum(string $fqn)
    {
        $map = Relation::morphMap($fqn);
        $this->assertSame($fqn, $map);
        $this->assertSame($fqn, Relation::morphMap());
    }

    #[TestWith([MorphMap::class])]
    public function testMorphMapEnumMerge(string $fqn)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Enum morph maps cannot be merged!');
        Relation::morphMap($fqn, true);
    }

    #[TestWith([MorphMap::class, [ 'a' => A::class ]])]
    public function testMorphMapEnumMergePrevious(string $first, array $second)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Enum morph maps cannot be merged!');
        Relation::morphMap($first);
        Relation::morphMap($second, true);
    }

    #[TestWith(['gibberish'])]
    public function testMorphMapArbitraryString(string $input)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mapping must be a an array, null, or a backed enum!');
        Relation::morphMap($input);
    }

    #[TestWith([[ A::class, B::class ], ['a_s' => A::class, 'b_s' => B::class]])]
    public function testMorphMapList(array $input, array $output)
    {
        $map = Relation::morphMap($input);
        $this->assertSame($output, $map);
        $this->assertSame($output, Relation::morphMap());
    }

    #[TestWith([[ 'a' => A::class ]])]
    public function testMorphMapAssociativeArray(array $map)
    {
        $res = Relation::morphMap($map);
        $this->assertSame($res, $map);
        $this->assertSame($res, Relation::morphMap());
    }

    #[TestWith(['A', A::class])]
    #[TestWith(['C', null])]
    public function testGetMorphedModelEnum(string $alias, ?string $model)
    {
        Relation::morphMap(MorphMap::class);
        $this->assertSame($model, Relation::getMorphedModel($alias));
    }

    #[TestWith(['a', A::class])]
    #[TestWith(['b', null])]
    public function testGetMorphedModelArray(string $alias, ?string $model)
    {
        Relation::morphMap([ 'a' => A::class ]);
        $this->assertSame($model, Relation::getMorphedModel($alias));
    }

    #[TestWith([A::class, 'A'])]
    #[TestWith([MorphMap::A, 'A'])]
    #[TestWith(['C', 'C'])]
    public function testGetMorphAliasEnum(string|MorphMap $className, string|MorphMap|null $alias)
    {
        Relation::morphMap(MorphMap::class);
        $this->assertSame($alias, Relation::getMorphAlias($className));
    }

    #[TestWith([A::class, 'a'])]
    #[TestWith(['gibberish', 'gibberish'])]
    public function testGetMorphAliasArray(string $className, string $alias)
    {
        Relation::morphMap([ 'a' => A::class ]);
        $this->assertSame($alias, Relation::getMorphAlias($className));
    }

    #[TestWith([MorphMap::A, A::class])]
    public function testGetMorphAliasArrayEnumClassName(string|MorphMap $className, string $alias)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class name cannot be an enum value when the morph map is not an enum!');
        Relation::morphMap([ 'a' => A::class ]);
        Relation::getMorphAlias($className);
    }
}

enum MorphMap: string
{
    case A = A::class;
    case B = B::class;
}

class A extends Model {}
class B extends Model {}
