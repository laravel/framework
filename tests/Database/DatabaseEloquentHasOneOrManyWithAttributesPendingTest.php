<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasOneOrManyWithAttributesPendingTest extends TestCase
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
    }

    public function testHasManyAddsAttributes(): void
    {
        $parentId = 123;
        $key = 'a key';
        $value = 'the value';

        $parent = new RelatedPendingAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasMany(RelatedPendingAttributesModel::class, 'parent_id')
            ->withAttributes([$key => $value], asConditions: false);

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->parent_id);
        $this->assertSame($value, $relatedModel->$key);
    }

    public function testHasOneAddsAttributes(): void
    {
        $parentId = 123;
        $key = 'a key';
        $value = 'the value';

        $parent = new RelatedPendingAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasOne(RelatedPendingAttributesModel::class, 'parent_id')
            ->withAttributes([$key => $value], asConditions: false);

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->parent_id);
        $this->assertSame($value, $relatedModel->$key);
    }

    public function testMorphManyAddsAttributes(): void
    {
        $parentId = 123;
        $key = 'a key';
        $value = 'the value';

        $parent = new RelatedPendingAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->morphMany(RelatedPendingAttributesModel::class, 'relatable')
            ->withAttributes([$key => $value], asConditions: false);

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->relatable_id);
        $this->assertSame($parent::class, $relatedModel->relatable_type);
        $this->assertSame($value, $relatedModel->$key);
    }

    public function testMorphOneAddsAttributes(): void
    {
        $parentId = 123;
        $key = 'a key';
        $value = 'the value';

        $parent = new RelatedPendingAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->morphOne(RelatedPendingAttributesModel::class, 'relatable')
            ->withAttributes([$key => $value], asConditions: false);

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->relatable_id);
        $this->assertSame($parent::class, $relatedModel->relatable_type);
        $this->assertSame($value, $relatedModel->$key);
    }

    public function testPendingAttributesCanBeOverridden(): void
    {
        $key = 'a key';
        $defaultValue = 'a value';
        $value = 'the value';

        $parent = new RelatedPendingAttributesModel;

        $relationship = $parent
            ->hasMany(RelatedPendingAttributesModel::class, 'relatable')
            ->withAttributes([$key => $defaultValue], asConditions: false);

        $relatedModel = $relationship->make([$key => $value]);

        $this->assertSame($value, $relatedModel->$key);
    }

    public function testQueryingDoesNotBreakWither(): void
    {
        $parentId = 123;
        $key = 'a key';
        $value = 'the value';

        $parent = new RelatedPendingAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasMany(RelatedPendingAttributesModel::class, 'parent_id')
            ->where($key, $value)
            ->withAttributes([$key => $value], asConditions: false);

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->parent_id);
        $this->assertSame($value, $relatedModel->$key);
    }

    public function testAttributesCanBeAppended(): void
    {
        $parent = new RelatedPendingAttributesModel;

        $relationship = $parent
            ->hasMany(RelatedPendingAttributesModel::class, 'parent_id')
            ->withAttributes(['a' => 'A'], asConditions: false)
            ->withAttributes(['b' => 'B'], asConditions: false)
            ->withAttributes(['a' => 'AA'], asConditions: false);

        $relatedModel = $relationship->make([
            'b' => 'BB',
            'c' => 'C',
        ]);

        $this->assertSame('AA', $relatedModel->a);
        $this->assertSame('BB', $relatedModel->b);
        $this->assertSame('C', $relatedModel->c);
    }

    public function testSingleAttributeApi(): void
    {
        $parent = new RelatedPendingAttributesModel;
        $key = 'attr';
        $value = 'Value';

        $relationship = $parent
            ->hasMany(RelatedPendingAttributesModel::class, 'parent_id')
            ->withAttributes($key, $value, asConditions: false);

        $relatedModel = $relationship->make();

        $this->assertSame($value, $relatedModel->$key);
    }

    public function testWheresAreNotSet(): void
    {
        $parentId = 123;
        $key = 'a key';
        $value = 'the value';

        $parent = new RelatedPendingAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasMany(RelatedPendingAttributesModel::class, 'parent_id')
            ->withAttributes([$key => $value], asConditions: false);

        $wheres = $relationship->toBase()->wheres;

        $this->assertContains([
            'type' => 'Basic',
            'column' => $parent->qualifyColumn('parent_id'),
            'operator' => '=',
            'value' => $parentId,
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'NotNull',
            'column' => $parent->qualifyColumn('parent_id'),
            'boolean' => 'and',
        ], $wheres);

        // Ensure no other wheres exist
        $this->assertCount(2, $wheres);
    }

    public function testNullValueIsAccepted(): void
    {
        $parentId = 123;
        $key = 'a key';

        $parent = new RelatedPendingAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasMany(RelatedPendingAttributesModel::class, 'parent_id')
            ->withAttributes([$key => null], asConditions: false);

        $wheres = $relationship->toBase()->wheres;
        $relatedModel = $relationship->make();

        $this->assertNull($relatedModel->$key);

        $this->assertContains([
            'type' => 'Basic',
            'column' => $parent->qualifyColumn('parent_id'),
            'operator' => '=',
            'value' => $parentId,
            'boolean' => 'and',
        ], $wheres);

        $this->assertContains([
            'type' => 'NotNull',
            'column' => $parent->qualifyColumn('parent_id'),
            'boolean' => 'and',
        ], $wheres);

        // Ensure no other wheres exist
        $this->assertCount(2, $wheres);
    }

    public function testOneKeepsAttributesFromHasMany(): void
    {
        $parentId = 123;
        $key = 'a key';
        $value = 'the value';

        $parent = new RelatedPendingAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasMany(RelatedPendingAttributesModel::class, 'parent_id')
            ->withAttributes([$key => $value], asConditions: false)
            ->one();

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->parent_id);
        $this->assertSame($value, $relatedModel->$key);
    }

    public function testOneKeepsAttributesFromMorphMany(): void
    {
        $parentId = 123;
        $key = 'a key';
        $value = 'the value';

        $parent = new RelatedPendingAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->morphMany(RelatedPendingAttributesModel::class, 'relatable')
            ->withAttributes([$key => $value], asConditions: false)
            ->one();

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->relatable_id);
        $this->assertSame($parent::class, $relatedModel->relatable_type);
        $this->assertSame($value, $relatedModel->$key);
    }

    public function testHasManyAddsCastedAttributes(): void
    {
        $parentId = 123;

        $parent = new RelatedPendingAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasMany(RelatedPendingAttributesModel::class, 'parent_id')
            ->withAttributes(['is_admin' => 1], asConditions: false);

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->parent_id);
        $this->assertSame(true, $relatedModel->is_admin);
    }
}

class RelatedPendingAttributesModel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_admin' => 'boolean',
    ];
}
