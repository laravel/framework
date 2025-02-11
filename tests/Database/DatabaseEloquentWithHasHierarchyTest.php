<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\HasHierarchy;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentWithHasHierarchyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('test_categories', function ($table) {
            $table->increments('id');
            $table->integer('parent_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('test_categories_with_uuid', function ($table) {
            $table->uuid('id');
            $table->string('parent_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Test retrieving the root ID for a simple hierarchy.
     */
    public function testGetHierarchyRootIdForSimpleHierarchy()
    {
        $this->createCategories();
        $category = TestCategory::find(3);

        $rootId = $category->getHierarchyRootId();
        $this->assertEquals(1, $rootId);
    }

    /**
     * Test retrieving null if the record has no root record.
     */
    public function testGetHierarchyRootIdForRootRecord()
    {
        $this->createCategories();
        $category = TestCategory::find(1);

        $rootId = $category->getHierarchyRootId();
        $this->assertNull($rootId);
    }

    /**
     * Test retrieving the root ID for a deep hierarchy.
     */
    public function testGetHierarchyRootIdForDeepHierarchy()
    {
        TestCategory::insert([
            ['id' => 1, 'name' => 'Root', 'parent_id' => null],
            ['id' => 2, 'name' => 'Level 1', 'parent_id' => 1],
            ['id' => 3, 'name' => 'Level 2', 'parent_id' => 2],
            ['id' => 4, 'name' => 'Level 3', 'parent_id' => 3],
            ['id' => 5, 'name' => 'Level 4', 'parent_id' => 4],
        ]);

        $category = TestCategory::find(5);
        $rootId = $category->getHierarchyRootId();

        $this->assertSame('integer', gettype($rootId));
        $this->assertEquals(1, $rootId);
    }

    /**
     * Test retrieving the root ID for UUID-based hierarchy.
     */
    public function testGetHierarchyRootIdForUuidHierarchy()
    {
        $this->createCategoriesWithUuid();
        $category = TestCategoryWithUuid::find('550e8400-e29b-41d4-a716-446655440002');

        $rootId = $category->getHierarchyRootId();

        $this->assertSame('string', gettype($rootId));
        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $rootId);
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('test_categories');
        $this->schema()->drop('test_categories_with_uuid');
    }

    /**
     * Helpers...
     *
     * @return void
     */
    protected function createCategories(): void
    {
        TestCategory::insert([
            ['id' => 1, 'name' => 'Electronics', 'parent_id' => null],
            ['id' => 2, 'name' => 'Laptops', 'parent_id' => 1],
            ['id' => 3, 'name' => 'Gaming Laptops', 'parent_id' => 2],
            ['id' => 4, 'name' => 'Ultrabooks', 'parent_id' => 2],
            ['id' => 5, 'name' => 'Smartphones', 'parent_id' => 1],
            ['id' => 6, 'name' => 'Accessories', 'parent_id' => null],
            ['id' => 7, 'name' => 'Headphones', 'parent_id' => 6],
        ]);
    }

    /**
     * Helpers...
     *
     * @return void
     */
    protected function createCategoriesWithUuid(): void
    {
        TestCategoryWithUuid::insert([
            ['id' => '550e8400-e29b-41d4-a716-446655440000', 'name' => 'Electronics', 'parent_id' => null],
            ['id' => '550e8400-e29b-41d4-a716-446655440001', 'name' => 'Laptops', 'parent_id' => '550e8400-e29b-41d4-a716-446655440000'],
            ['id' => '550e8400-e29b-41d4-a716-446655440002', 'name' => 'Gaming Laptops', 'parent_id' => '550e8400-e29b-41d4-a716-446655440001'],
            ['id' => '550e8400-e29b-41d4-a716-446655440003', 'name' => 'Ultrabooks', 'parent_id' => '550e8400-e29b-41d4-a716-446655440001'],
            ['id' => '550e8400-e29b-41d4-a716-446655440004', 'name' => 'Smartphones', 'parent_id' => '550e8400-e29b-41d4-a716-446655440000'],
            ['id' => '550e8400-e29b-41d4-a716-446655440005', 'name' => 'Accessories', 'parent_id' => null],
            ['id' => '550e8400-e29b-41d4-a716-446655440006', 'name' => 'Headphones', 'parent_id' => '550e8400-e29b-41d4-a716-446655440005'],
        ]);
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

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Model::getConnectionResolver()->connection();
    }
}

/**
 * Eloquent Models...
 */
class TestCategory extends Model
{
    use HasHierarchy;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'test_categories';
}

/**
 * Eloquent Models...
 */
class TestCategoryWithUuid extends Model
{
    use HasHierarchy;
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'test_categories_with_uuid';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
}
