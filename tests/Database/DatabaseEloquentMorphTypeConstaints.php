<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

// ==================== Interfaces and Abstract Classes ====================

interface Flyable
{
    public function fly();
}

interface Identifiable
{
    public function getIdentifier();
}

abstract class Vehicle
{
    abstract public function getType();
}

// ==================== Models ====================

class Bird extends Model implements Flyable, Identifiable
{
    protected $guarded = [];

    public function fly()
    {
        return 'Bird is flying';
    }

    public function getIdentifier()
    {
        return 'bird-'.$this->id;
    }
}

class Airplane extends Model implements Flyable
{
    protected $guarded = [];

    public function fly()
    {
        return 'Airplane is flying';
    }
}

class Car extends Vehicle
{
    protected $guarded = [];

    public function getType()
    {
        return 'Land Vehicle';
    }
}

class Boat extends Vehicle
{
    protected $guarded = [];

    public function getType()
    {
        return 'Water Vehicle';
    }
}

class Animal extends Model
{
    protected $guarded = [];

    public function flyable()
    {
        return $this->morphTo()->mustImplement(Flyable::class);
    }

    public function features()
    {
        return $this->morphMany(Feature::class, 'featureable')->mustImplement(Identifiable::class);
    }

    public function mainFeature()
    {
        return $this->morphOne(Feature::class, 'featureable')->mustImplement(Identifiable::class);
    }
}

class Feature extends Model implements Identifiable
{
    protected $guarded = [];

    public function getIdentifier()
    {
        return 'feature-'.$this->id;
    }

    public function featureable()
    {
        return $this->morphTo();
    }
}

class Tag extends Model
{
    protected $guarded = [];

    public function vehicles()
    {
        return $this->morphedByMany(Car::class, 'taggable')
            ->withPivot('notes')
            ->withTimestamps()
            ->mustExtend(Vehicle::class);
    }
}

class MorphTypeConstraintsTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->setAsGlobal();
        $db->bootEloquent();

        $this->createSchema();
        $this->seedData();
    }

    protected function createSchema()
    {
        DB::schema()->create('birds', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        DB::schema()->create('airplanes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        DB::schema()->create('cars', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        DB::schema()->create('boats', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        DB::schema()->create('animals', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->morphs('flyable');
            $table->timestamps();
        });

        DB::schema()->create('features', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->morphs('featureable');
            $table->timestamps();
        });

        DB::schema()->create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        DB::schema()->create('taggables', function (Blueprint $table) {
            $table->integer('tag_id');
            $table->morphs('taggable');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    protected function seedData()
    {
        Bird::create(['name' => 'Eagle']);
        Bird::create(['name' => 'Sparrow']);
        Airplane::create(['name' => 'Boeing 747']);
        Car::create(['name' => 'Ferrari']);
        Boat::create(['name' => 'Yacht']);

        // Valid relationships with flyable objects
        Animal::create([
            'name' => 'Eagle',
            'flyable_type' => Bird::class,
            'flyable_id' => 1,
        ]);

        Animal::create([
            'name' => 'Boeing',
            'flyable_type' => Airplane::class,
            'flyable_id' => 1,
        ]);

        // Invalid relationship - Car doesn't implement Flyable
        Animal::create([
            'name' => 'Ferrari',
            'flyable_type' => Car::class,
            'flyable_id' => 1,
        ]);

        // Features for birds (implements Identifiable)
        Feature::create([
            'name' => 'Wings',
            'featureable_type' => Bird::class,
            'featureable_id' => 1,
        ]);

        Feature::create([
            'name' => 'Beak',
            'featureable_type' => Bird::class,
            'featureable_id' => 1,
        ]);

        // Tags for vehicles
        $tag = Tag::create(['name' => 'Fast']);
        $tag->vehicles()->attach(1, ['notes' => 'Very fast car']);
    }

    // ==================== Tests for MorphTo ====================

    public function testMorphToValidImplementations()
    {
        $eagle = Animal::where('name', 'Eagle')->first();
        $this->assertInstanceOf(Flyable::class, $eagle->flyable);
        $this->assertEquals('Bird is flying', $eagle->flyable->fly());

        $boeing = Animal::where('name', 'Boeing')->first();
        $this->assertInstanceOf(Flyable::class, $boeing->flyable);
        $this->assertEquals('Airplane is flying', $boeing->flyable->fly());
    }

    public function testMorphToMultipleInterfaces()
    {
        // Redefine the relationship to require multiple interfaces
        $animal = new class extends Animal
        {
            public function flyable()
            {
                return $this->morphTo()->mustImplement([Flyable::class, Identifiable::class]);
            }
        };

        // This should work with Bird (implements both interfaces)
        $eagle = $animal::where('name', 'Eagle')->first();
        $this->assertInstanceOf(Flyable::class, $eagle->flyable);
        $this->assertInstanceOf(Identifiable::class, $eagle->flyable);

        // This should fail with Airplane (only implements Flyable)
        $this->expectException(\RuntimeException::class);
        $boeing = $animal::where('name', 'Boeing')->first();
        $boeing->flyable;
    }

    public function testMorphToInvalidImplementation()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Related model [Tests\Database\Eloquent\Car] must implement interface [Tests\Database\Eloquent\Flyable]');

        $ferrari = Animal::where('name', 'Ferrari')->first();
        $ferrari->flyable; // This should throw an exception
    }

    public function testMorphToEagerLoading()
    {
        // This should throw an exception during eager loading due to Car not implementing Flyable
        $this->expectException(\RuntimeException::class);

        Animal::with('flyable')->get();
    }

    public function testMorphToWithNullRelationship()
    {
        // Create an animal with no flyable relationship
        $animal = Animal::create([
            'name' => 'Human',
            'flyable_type' => null,
            'flyable_id' => null,
        ]);

        // This shouldn't throw an exception despite the constraint
        $this->assertNull($animal->flyable);
    }

    public function testMorphToWithAbstractClass()
    {
        // Redefine the relationship to require an abstract class
        $animal = new class extends Animal {
            public function flyable()
            {
                return $this->morphTo()->mustExtend(Vehicle::class);
            }
        };

        // This should work with Car (extends Vehicle)
        $ferrari = $animal::where('name', 'Ferrari')->first();
        $this->assertInstanceOf(Vehicle::class, $ferrari->flyable);

        // This should fail with Bird (doesn't extend Vehicle)
        $this->expectException(\RuntimeException::class);
        $eagle = $animal::where('name', 'Eagle')->first();
        $eagle->flyable;
    }

    // ==================== Tests for MorphMany ====================

    public function testMorphManyValidImplementations()
    {
        $bird = Bird::find(1);
        Feature::create([
            'name' => 'Feathers',
            'featureable_type' => Bird::class,
            'featureable_id' => $bird->id,
        ]);

        $animal = Animal::where('name', 'Eagle')->first();
        $features = $animal->features;

        $this->assertCount(2, $features);
        foreach ($features as $feature) {
            $this->assertInstanceOf(Identifiable::class, $feature);
        }
    }

    public function testMorphManyEagerLoading()
    {
        $bird = Bird::find(1);
        $birds = Bird::with('features')->get();

        foreach ($birds as $loadedBird) {
            foreach ($loadedBird->features as $feature) {
                $this->assertInstanceOf(Identifiable::class, $feature);
            }
        }
    }

    // ==================== Tests for MorphOne ====================

    public function testMorphOneValidImplementation()
    {
        $bird = Bird::find(1);
        $feature = $bird->mainFeature;

        $this->assertInstanceOf(Identifiable::class, $feature);
        $this->assertEquals('feature-1', $feature->getIdentifier());
    }

    // ==================== Tests for MorphToMany ====================

    public function testMorphToManyValidImplementation()
    {
        $tag = Tag::find(1);
        $vehicles = $tag->vehicles;

        $this->assertCount(1, $vehicles);
        foreach ($vehicles as $vehicle) {
            $this->assertInstanceOf(Vehicle::class, $vehicle);
        }
    }

    public function testMorphToManyWithInvalidAbstractClass()
    {
        // Create a tag that's incorrectly associated with a Bird
        DB::table('taggables')->insert([
            'tag_id' => 1,
            'taggable_type' => Bird::class,
            'taggable_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // This should throw an exception when loading
        $this->expectException(\RuntimeException::class);
        $tag = Tag::find(1);
        $tag->vehicles()->get();
    }

    // ==================== Tests for edge cases ====================

    public function testNonExistentInterface()
    {
        $this->expectException(\InvalidArgumentException::class);

        $animal = new Animal();
        $animal->morphTo()->mustImplement('NonExistentInterface');
    }

    public function testNonExistentAbstractClass()
    {
        $this->expectException(\InvalidArgumentException::class);

        $animal = new Animal();
        $animal->morphTo()->mustExtend('NonExistentClass');
    }

    public function testNonAbstractClass()
    {
        $this->expectException(\InvalidArgumentException::class);

        $animal = new Animal();
        $animal->morphTo()->mustExtend(Bird::class); // Bird is not abstract
    }

    public function testCustomMorphClass()
    {
        // Create a class with a custom morph class name
        $customBird = new class extends Bird
        {
            public function getMorphClass()
            {
                return 'custom_bird';
            }
        };

        // Create an animal with the custom morph type
        $animal = Animal::create([
            'name' => 'CustomBird',
            'flyable_type' => 'custom_bird',
            'flyable_id' => 1,
        ]);

        // This should work without throwing an exception
        Model::addMorphMap(['custom_bird' => get_class($customBird)]);
        $this->assertInstanceOf(Flyable::class, $animal->flyable);
    }
}
