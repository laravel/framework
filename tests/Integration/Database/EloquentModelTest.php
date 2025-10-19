<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\RequiresPhp;

class EloquentModelTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('nullable_date')->nullable();
        });

        Schema::create('test_model2', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('title');
        });
    }

    public function testUserCanUpdateNullableDate()
    {
        $user = TestModel1::create([
            'nullable_date' => null,
        ]);

        $user->fill([
            'nullable_date' => $now = Carbon::now(),
        ]);
        $this->assertTrue($user->isDirty('nullable_date'));

        $user->save();
        $this->assertEquals($now->toDateString(), $user->nullable_date->toDateString());
    }

    public function testAttributeChanges()
    {
        $user = TestModel2::create([
            'name' => $originalName = Str::random(), 'title' => Str::random(),
        ]);

        $this->assertEmpty($user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertEmpty($user->getPrevious());
        $this->assertFalse($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->name = $overrideName = Str::random();

        $this->assertEquals(['name' => $overrideName], $user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertEmpty($user->getPrevious());
        $this->assertTrue($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->save();

        $this->assertEmpty($user->getDirty());
        $this->assertEquals(['name' => $overrideName], $user->getChanges());
        $this->assertEquals(['name' => $originalName], $user->getPrevious());
        $this->assertTrue($user->wasChanged());
        $this->assertTrue($user->wasChanged('name'));
    }

    public function testDiscardChanges()
    {
        $user = TestModel2::create([
            'name' => $originalName = Str::random(), 'title' => Str::random(),
        ]);

        $this->assertEmpty($user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertEmpty($user->getPrevious());
        $this->assertFalse($user->isDirty());
        $this->assertFalse($user->wasChanged());

        $user->name = $overrideName = Str::random();

        $this->assertEquals(['name' => $overrideName], $user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertEmpty($user->getPrevious());
        $this->assertTrue($user->isDirty());
        $this->assertFalse($user->wasChanged());
        $this->assertSame($originalName, $user->getOriginal('name'));
        $this->assertSame($overrideName, $user->getAttribute('name'));

        $user->discardChanges();

        $this->assertEmpty($user->getDirty());
        $this->assertEmpty($user->getChanges());
        $this->assertEmpty($user->getPrevious());
        $this->assertSame($originalName, $user->getOriginal('name'));
        $this->assertSame($originalName, $user->getAttribute('name'));

        $user->save();
        $this->assertFalse($user->wasChanged());
        $this->assertEmpty($user->getChanges());
        $this->assertEmpty($user->getPrevious());
    }

    public function testInsertRecordWithReservedWordFieldName()
    {
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->timestamp('start');
            $table->timestamp('end')->nullable();
            $table->boolean('analyze');
        });

        $model = new class extends Model
        {
            protected $table = 'actions';
            protected $guarded = ['id'];
            public $timestamps = false;
        };

        $model->newInstance()->create([
            'label' => 'test',
            'start' => '2023-01-01 00:00:00',
            'end' => '2024-01-01 00:00:00',
            'analyze' => true,
        ]);

        $this->assertDatabaseHas('actions', [
            'label' => 'test',
            'start' => '2023-01-01 00:00:00',
            'end' => '2024-01-01 00:00:00',
            'analyze' => true,
        ]);
    }

    #[RequiresPhp('>=8.4')]
    public function testModelWithPropertyHooksCanBeSerialized()
    {
        $model = new TestModelWithPropertyHooks;
        $model->first_name = 'John';
        $model->last_name = 'Doe';

        // Access the property hook to ensure it works
        $this->assertEquals('John Doe', $model->full_name);

        $serialized = serialize($model);

        $this->assertIsString($serialized);

        // Verify unserialization works
        $unserialized = unserialize($serialized);

        $this->assertEquals('John', $unserialized->first_name);
        $this->assertEquals('Doe', $unserialized->last_name);
        // Property hook should still work after unserialization
        $this->assertEquals('John Doe', $unserialized->full_name);
    }

    #[RequiresPhp('>=8.4')]
    public function testModelWithMultiplePropertyHooksCanBeSerialized()
    {
        $model = new TestModelWithMultiplePropertyHooks;
        $model->first_name = 'John';
        $model->last_name = 'Doe';
        $model->middle_name = 'Smith';

        $this->assertEquals('Doe John Smith', $model->full_name);
        $this->assertEquals('John Doe', $model->short_name);

        $serialized = serialize($model);
        $this->assertIsString($serialized);

        // Test unserialization
        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(Model::class, $unserialized);

        // Verify the property hooks still work after unserialization
        $this->assertEquals('Doe John Smith', $unserialized->full_name);
        $this->assertEquals('John Doe', $unserialized->short_name);
    }

    #[RequiresPhp('>=8.4')]
    public function testModelWithSetterPropertyHookCanBeSerialized()
    {
        $model = new TestModelWithSetterPropertyHook;
        $model->email = '  JOHN@EXAMPLE.COM  ';

        // Verify setter hook worked
        $this->assertEquals('john@example.com', $model->email);

        // Test serialization
        $serialized = serialize($model);
        $unserialized = unserialize($serialized);

        // Verify data persists after unserialization
        $this->assertEquals('john@example.com', $unserialized->email);
    }

    #[RequiresPhp('>=8.4')]
    public function testModelWithPropertyHooksCanBeQueuedForRedis()
    {
        $model = new TestModelWithPropertyHooks;
        $model->first_name = 'John';
        $model->last_name = 'Doe';
        $model->middle_name = 'Smith';

        $payload = serialize([
            'model' => $model,
            'some_data' => 'test'
        ]);

        $this->assertIsString($payload);


        $restored = unserialize($payload);

        $this->assertIsArray($restored);
        $this->assertInstanceOf(Model::class, $restored['model']);
        $this->assertEquals('John', $restored['model']->first_name);
        $this->assertEquals('John Doe', $restored['model']->full_name);
    }

    public function testModelWithoutPropertyHooksStillWorks()
    {
        $model = new TestModel2;
        $model->name = 'John Doe';
        $model->title = 'Developer';

        $serialized = serialize($model);
        $unserialized = unserialize($serialized);

        $this->assertEquals('John Doe', $unserialized->name);
        $this->assertEquals('Developer', $unserialized->title);
    }

    #[RequiresPhp('>=8.4')]
    public function testModelWithMixedPropertiesAndHooks()
    {
        // Test a model with both regular properties and property hooks
        $model = new TestModelWithMixedPropertiesAndHooks;
        $model->first_name = 'john';
        $model->last_name = 'doe';
        $model->metadata = ['role' => 'admin'];

        $this->assertEquals('JOHN', $model->display_name);

        $serialized = serialize($model);
        $unserialized = unserialize($serialized);

        // Regular properties should be preserved
        $this->assertEquals('john', $unserialized->first_name);
        $this->assertEquals('doe', $unserialized->last_name);
        $this->assertEquals(['role' => 'admin'], $unserialized->metadata);

        // Property hook should still work
        $this->assertEquals('JOHN', $unserialized->display_name);
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['nullable_date' => 'datetime'];
}

class TestModel2 extends Model
{
    public $table = 'test_model2';
    public $timestamps = false;
    protected $guarded = [];
}

// PHP 8.4+ Property Hooks Test Models
if (PHP_VERSION_ID >= 80400) {
    class TestModelWithPropertyHooks extends Model
    {
        protected $table = 'test_model2';
        public $timestamps = false;
        protected $fillable = ['first_name', 'last_name', 'middle_name'];

        // Property hook - virtual property
        public string $full_name {
            get => "{$this->first_name} {$this->last_name}";
        }
    }

    class TestModelWithMultiplePropertyHooks extends Model
    {
        protected $table = 'test_model2';
        public $timestamps = false;
        protected $fillable = ['first_name', 'last_name', 'middle_name'];

        // Multiple property hooks
        public string $full_name {
            get => trim("{$this->last_name} {$this->first_name} {$this->middle_name}");
        }

        public string $short_name {
            get => "{$this->first_name} {$this->last_name}";
        }
    }

    class TestModelWithSetterPropertyHook extends Model
    {
        protected $table = 'test_model2';
        public $timestamps = false;
        protected $fillable = ['email'];

        private string $_email = '';

        // Property hook with both get and set
        public string $email {
            get => $this->_email;
            set (string $value) {
                $this->_email = strtolower(trim($value));
            }
        }
    }

    class TestModelWithMixedPropertiesAndHooks extends Model
    {
        protected $table = 'test_model2';
        public $timestamps = false;
        protected $fillable = ['first_name', 'last_name'];

        // Regular property (will be serialized)
        public $metadata = ['key' => 'value'];

        // Property hook (should NOT be serialized as it's virtual)
        public string $display_name {
            get => strtoupper($this->first_name ?? '');
        }
    }
}
