<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentVirtualJsonCastTest extends TestCase
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

    protected function createSchema()
    {
        $this->schema()->create('test_models', function ($table) {
            $table->increments('id');
            $table->json('meta')->nullable();
            $table->json('options')->nullable();
            $table->integer('volume')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Tear down the database schema.
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('test_models');

        parent::tearDown();
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
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

    public function testVirtualCastRetrievesValueFromJsonColumn()
    {
        $model = new VirtualCastTestModel;
        $model->setRawAttributes([
            'id' => 1,
            'meta' => json_encode(['settings' => ['volume' => '75']]),
        ]);

        $this->assertSame(75, $model->volume);
        $this->assertIsInt($model->volume);
    }

    public function testVirtualCastSetsValueInJsonColumn()
    {
        $model = new VirtualCastTestModel;
        $model->volume = 100;

        $this->assertIsString($model->getAttributes()['meta']);
        $metaData = json_decode($model->getAttributes()['meta'], true);
        $this->assertSame(100, $metaData['settings']['volume']);
    }

    public function testVirtualCastSetAndRetrieve()
    {
        $model = new VirtualCastTestModel;
        $model->volume = '50';

        $this->assertSame(50, $model->volume);
        $this->assertIsInt($model->volume);
    }

    public function testVirtualCastWorksWithStringType()
    {
        $model = new VirtualCastTestModel;
        $model->title = 'Test Title';

        $this->assertSame('Test Title', $model->title);
        $this->assertIsString($model->title);

        $metaData = json_decode($model->getAttributes()['meta'], true);
        $this->assertSame('Test Title', $metaData['settings']['title']);
    }

    public function testVirtualCastWorksWithBooleanType()
    {
        $model = new VirtualCastTestModel;
        $model->enabled = 1;

        $this->assertTrue($model->enabled);
        $this->assertIsBool($model->enabled);

        $metaData = json_decode($model->getAttributes()['meta'], true);
        $this->assertSame(1, $metaData['settings']['enabled']);
    }

    public function testRealColumnTakesPriorityOverVirtualCast()
    {
        $model = new VirtualCastTestModel;

        // Set the real column directly
        $model->setRawAttributes([
            'volume' => 200,
            'meta' => json_encode(['settings' => ['volume' => '75']]),
        ]);

        // The real column should take priority
        $this->assertSame(200, $model->volume);
    }

    public function testRealColumnTakesPriorityWhenSettingValue()
    {
        $model = new VirtualCastTestModel;

        // First, set the real column
        $model->setRawAttributes(['volume' => 150]);

        // Now try to set via attribute
        $model->volume = 100;

        // It should update the real column, not the JSON
        $this->assertSame(100, $model->getAttributes()['volume']);
        $this->assertFalse(isset($model->getAttributes()['meta']));
    }

    public function testDeepNestedVirtualCast()
    {
        $model = new VirtualCastTestModel;
        $model->color = 'blue';

        $this->assertSame('blue', $model->color);
        $this->assertIsString($model->color);

        $optionsData = json_decode($model->getAttributes()['options'], true);
        $this->assertSame('blue', $optionsData['ui']['theme']['color']);
    }

    public function testDeepNestedVirtualCastRetrieval()
    {
        $model = new VirtualCastTestModel;
        $model->setRawAttributes([
            'id' => 1,
            'options' => json_encode(['ui' => ['theme' => ['color' => 'red', 'mode' => 'dark']]]),
        ]);

        $this->assertSame('red', $model->color);
        $this->assertSame('dark', $model->mode);
    }

    public function testVirtualCastWithNullValue()
    {
        $model = new VirtualCastTestModel;
        $model->setRawAttributes([
            'id' => 1,
            'meta' => null,
        ]);

        $this->assertNull($model->volume);
    }

    public function testVirtualCastWithEmptyJson()
    {
        $model = new VirtualCastTestModel;
        $model->setRawAttributes([
            'id' => 1,
            'meta' => json_encode([]),
        ]);

        $this->assertNull($model->volume);
    }

    public function testVirtualCastPreservesExistingJsonData()
    {
        $model = new VirtualCastTestModel;
        $model->setRawAttributes([
            'meta' => json_encode(['settings' => ['volume' => '30', 'other' => 'value']]),
        ]);

        $model->volume = 60;

        $metaData = json_decode($model->getAttributes()['meta'], true);
        $this->assertSame(60, $metaData['settings']['volume']);
        $this->assertSame('value', $metaData['settings']['other']);
    }

    public function testVirtualCastWithDatabasePersistence()
    {
        VirtualCastTestModel::create([
            'color' => 'green',
            'mode' => 'light',
        ]);

        $model = VirtualCastTestModel::first();

        $this->assertSame('green', $model->color);
        $this->assertSame('light', $model->mode);
        $this->assertIsString($model->color);
        $this->assertIsString($model->mode);
    }

    public function testVirtualCastUpdatesPersisted()
    {
        $model = VirtualCastTestModel::create(['color' => 'yellow']);

        $model->color = 'purple';
        $model->save();

        $freshModel = VirtualCastTestModel::find($model->id);
        $this->assertSame('purple', $freshModel->color);
    }

    public function testMultipleVirtualCastsOnSameJsonColumn()
    {
        $model = new VirtualCastTestModel;
        $model->volume = 70;
        $model->title = 'Multiple Casts';
        $model->enabled = true;

        $this->assertSame(70, $model->volume);
        $this->assertSame('Multiple Casts', $model->title);
        $this->assertTrue($model->enabled);

        $metaData = json_decode($model->getAttributes()['meta'], true);
        $this->assertSame(70, $metaData['settings']['volume']);
        $this->assertSame('Multiple Casts', $metaData['settings']['title']);
        $this->assertTrue($metaData['settings']['enabled']);
    }

    public function testVirtualCastWithFloatType()
    {
        $model = new VirtualCastTestModel;
        $model->setRawAttributes([
            'meta' => json_encode(['settings' => ['rating' => '4.5']]),
        ]);

        $this->assertSame(4.5, $model->rating);
        $this->assertIsFloat($model->rating);
    }

    public function testVirtualCastIsDirtyDetection()
    {
        $model = VirtualCastTestModel::create(['volume' => 50]);

        $this->assertFalse($model->isDirty('meta'));

        $model->volume = 60;

        $this->assertTrue($model->isDirty('meta'));
    }

    public function testVirtualCastWithArrayType()
    {
        $model = new VirtualCastTestModel;
        $model->setRawAttributes([
            'meta' => json_encode(['settings' => ['tags' => ['php', 'laravel', 'testing']]]),
        ]);

        $this->assertIsArray($model->tags);
        $this->assertSame(['php', 'laravel', 'testing'], $model->tags);
    }

    public function testVirtualAttributesWithAliasing()
    {
        $model = new AliasingTestModel;
        $model->setRawAttributes([
            'meta' => json_encode([
                'name' => 'John Doe',
                'organization' => ['name' => 'Acme Corp'],
            ]),
        ]);

        // Test that aliases work correctly
        $this->assertSame('John Doe', $model->meta_name);
        $this->assertSame('Acme Corp', $model->organization_name);

        // Test setting via aliases
        $model->meta_name = 'Jane Smith';
        $model->organization_name = 'Tech Inc';

        $metaData = json_decode($model->getAttributes()['meta'], true);
        $this->assertSame('Jane Smith', $metaData['name']);
        $this->assertSame('Tech Inc', $metaData['organization']['name']);
    }
}

class VirtualCastTestModel extends Model
{
    protected $table = 'test_models';
    protected $guarded = [];

    protected function virtualJsonAttributes(): array
    {
        return [
            'meta->settings->volume' => 'volume',
            'meta->settings->title' => 'title',
            'meta->settings->enabled' => 'enabled',
            'meta->settings->rating' => 'rating',
            'meta->settings->tags' => 'tags',
            'options->ui->theme->color' => 'color',
            'options->ui->theme->mode' => 'mode',
        ];
    }

    protected function casts(): array
    {
        return [
            'volume' => 'integer',
            'title' => 'string',
            'enabled' => 'boolean',
            'rating' => 'float',
            'tags' => 'array',
            'color' => 'string',
            'mode' => 'string',
        ];
    }
}

class AliasingTestModel extends Model
{
    protected $table = 'test_models';
    protected $guarded = [];

    protected function virtualJsonAttributes(): array
    {
        return [
            'meta->name' => 'meta_name',
            'meta->organization->name' => 'organization_name',
        ];
    }

    protected function casts(): array
    {
        return [
            'meta_name' => 'string',
            'organization_name' => 'string',
        ];
    }
}
