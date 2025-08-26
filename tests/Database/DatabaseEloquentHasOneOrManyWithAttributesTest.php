<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasOneOrManyWithAttributesTest extends TestCase
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

        $parent = new RelatedWithAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasMany(RelatedWithAttributesModel::class, 'parent_id')
            ->withAttributes([$key => $value]);

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->parent_id);
        $this->assertSame($value, $relatedModel->$key);
    }

    public function testHasOneAddsAttributes(): void
    {
        $parentId = 123;
        $key = 'a key';
        $value = 'the value';

        $parent = new RelatedWithAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasOne(RelatedWithAttributesModel::class, 'parent_id')
            ->withAttributes([$key => $value]);

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->parent_id);
        $this->assertSame($value, $relatedModel->$key);
    }

    public function testMorphManyAddsAttributes(): void
    {
        $parentId = 123;
        $key = 'a key';
        $value = 'the value';

        $parent = new RelatedWithAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->morphMany(RelatedWithAttributesModel::class, 'relatable')
            ->withAttributes([$key => $value]);

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

        $parent = new RelatedWithAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->morphOne(RelatedWithAttributesModel::class, 'relatable')
            ->withAttributes([$key => $value]);

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->relatable_id);
        $this->assertSame($parent::class, $relatedModel->relatable_type);
        $this->assertSame($value, $relatedModel->$key);
    }

    public function testWithAttributesCanBeOverridden(): void
    {
        $key = 'a key';
        $defaultValue = 'a value';
        $value = 'the value';

        $parent = new RelatedWithAttributesModel;

        $relationship = $parent
            ->hasMany(RelatedWithAttributesModel::class, 'relatable')
            ->withAttributes([$key => $defaultValue]);

        $relatedModel = $relationship->make([$key => $value]);

        $this->assertSame($value, $relatedModel->$key);
    }

    public function testQueryingDoesNotBreakWither(): void
    {
        $parentId = 123;
        $key = 'a key';
        $value = 'the value';

        $parent = new RelatedWithAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasMany(RelatedWithAttributesModel::class, 'parent_id')
            ->where($key, $value)
            ->withAttributes([$key => $value]);

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->parent_id);
        $this->assertSame($value, $relatedModel->$key);
    }

    public function testAttributesCanBeAppended(): void
    {
        $parent = new RelatedWithAttributesModel;

        $relationship = $parent
            ->hasMany(RelatedWithAttributesModel::class, 'parent_id')
            ->withAttributes(['a' => 'A'])
            ->withAttributes(['b' => 'B'])
            ->withAttributes(['a' => 'AA']);

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
        $parent = new RelatedWithAttributesModel;
        $key = 'attr';
        $value = 'Value';

        $relationship = $parent
            ->hasMany(RelatedWithAttributesModel::class, 'parent_id')
            ->withAttributes($key, $value);

        $relatedModel = $relationship->make();

        $this->assertSame($value, $relatedModel->$key);
    }

    public function testWheresAreSet(): void
    {
        $parentId = 123;
        $key = 'a key';
        $value = 'the value';

        $parent = new RelatedWithAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasMany(RelatedWithAttributesModel::class, 'parent_id')
            ->withAttributes([$key => $value]);

        $wheres = $relationship->toBase()->wheres;

        $this->assertContains([
            'type' => 'Basic',
            'column' => 'related_with_attributes_models.'.$key,
            'operator' => '=',
            'value' => $value,
            'boolean' => 'and',
        ], $wheres);

        // Ensure this doesn't break the default where either.
        $this->assertContains([
            'type' => 'Basic',
            'column' => $parent->qualifyColumn('parent_id'),
            'operator' => '=',
            'value' => $parentId,
            'boolean' => 'and',
        ], $wheres);
    }

    public function testNullValueIsAccepted(): void
    {
        $parentId = 123;
        $key = 'a key';

        $parent = new RelatedWithAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasMany(RelatedWithAttributesModel::class, 'parent_id')
            ->withAttributes([$key => null]);

        $wheres = $relationship->toBase()->wheres;
        $relatedModel = $relationship->make();

        $this->assertNull($relatedModel->$key);

        $this->assertContains([
            'type' => 'Null',
            'column' => 'related_with_attributes_models.'.$key,
            'boolean' => 'and',
        ], $wheres);
    }

    public function testOneKeepsAttributesFromHasMany(): void
    {
        $parentId = 123;
        $key = 'a key';
        $value = 'the value';

        $parent = new RelatedWithAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasMany(RelatedWithAttributesModel::class, 'parent_id')
            ->withAttributes([$key => $value])
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

        $parent = new RelatedWithAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->morphMany(RelatedWithAttributesModel::class, 'relatable')
            ->withAttributes([$key => $value])
            ->one();

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->relatable_id);
        $this->assertSame($parent::class, $relatedModel->relatable_type);
        $this->assertSame($value, $relatedModel->$key);
    }

    public function testHasManyAddsCastedAttributes(): void
    {
        $parentId = 123;

        $parent = new RelatedWithAttributesModel;
        $parent->id = $parentId;

        $relationship = $parent
            ->hasMany(RelatedWithAttributesModel::class, 'parent_id')
            ->withAttributes(['is_admin' => 1]);

        $relatedModel = $relationship->make();

        $this->assertSame($parentId, $relatedModel->parent_id);
        $this->assertSame(true, $relatedModel->is_admin);
    }
}

class RelatedWithAttributesModel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_admin' => 'boolean',
    ];
}
