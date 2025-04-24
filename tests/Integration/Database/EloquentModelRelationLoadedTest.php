<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelRelationLoadedTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelRelationLoadedTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('ones', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('twos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('one_id');
        });

        Schema::create('threes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('two_id');
            $table->integer('one_id')->nullable();
        });
    }

    public function testWhenRelationIsInvalid()
    {
        $one = One::query()->create();
        $one->twos()->create();
        $one->load('twos');

        $this->assertFalse($one->relationLoaded(''));
        $this->assertFalse($one->relationLoaded('.'));
        $this->assertFalse($one->relationLoaded('null'));
        $this->assertFalse($one->relationLoaded('invalid'));
    }

    public function testWhenNestedRelationIsInvalid()
    {
        $one = One::query()->create();
        $two = $one->twos()->create();
        $two->threes()->create();
        $one->load('twos.threes');

        $this->assertFalse($one->relationLoaded('twos.'));
        $this->assertFalse($one->relationLoaded('twos.null'));
        $this->assertFalse($one->relationLoaded('twos.invalid'));
    }

    public function testWhenRelationNotLoaded()
    {
        $one = One::query()->create();

        $this->assertFalse($one->relationLoaded('twos'));
    }

    public function testWhenRelationLoaded()
    {
        $one = One::query()->create();
        $one->twos()->create();
        $one->load(['twos']);

        $this->assertTrue($one->relationLoaded('twos'));
    }

    public function testWhenChildRelationIsNotLoaded()
    {
        $one = One::query()->create();
        $two = $one->twos()->create();
        $two->threes()->create();
        $one->load('twos');

        $this->assertTrue($one->relationLoaded('twos'));
        $this->assertFalse($one->relationLoaded('twos.threes'));
    }

    public function testWhenChildRelationIsLoaded()
    {
        $one = One::query()->create();
        $two = $one->twos()->create();
        $two->threes()->create();
        $one->load('twos.threes');

        $this->assertTrue($one->relationLoaded('twos'));
        $this->assertTrue($one->relationLoaded('twos.threes'));
    }

    public function testWhenChildRecursiveRelationIsLoaded()
    {
        $one = One::query()->create();
        $two = $one->twos()->create();
        $two->threes()->create(['one_id' => $one->id]);
        $one->load('twos.threes.one');

        $this->assertTrue($one->relationLoaded('twos'));
        $this->assertTrue($one->relationLoaded('twos.threes'));
        $this->assertTrue($one->relationLoaded('twos.threes.one'));
    }

    public function testWhenParentRelationIsASingleInstance()
    {
        $one = One::query()->create();
        $two = $one->twos()->create();
        $three = $two->threes()->create();
        $three->load('two.one');

        $this->assertTrue($three->relationLoaded('two'));
        $this->assertTrue($three->relationLoaded('two.one'));
    }

    public function testWhenSetRelationsWithValidData()
    {
        $one = One::query()->create();
        $two = $one->twos()->create();
        $two->threes()->create();

        $one->setRelations([
            'twos' => $one->twos()->with('threes')->get(),
        ]);

        $this->assertTrue($one->relationLoaded('twos'));
        $this->assertTrue($one->relationLoaded('twos.threes'));
        $this->assertNull($one->getAttribute('twos.threes'));
    }

    public function testWhenGetNestedAttribute()
    {
        $one = One::query()->create();
        $two = $one->twos()->create();
        $two->threes()->create();
        $one->load('twos.threes');

        $this->assertNull($one->getAttribute('twos.threes'));
    }

    /**
     * This is a regression test to ensure previous functionality remains intact.
     */
    public function testWhenSetRelationsWithCustomData()
    {
        $one = One::query()->create();
        $two = $one->twos()->create();
        $three = $two->threes()->create();
        $three->load('two.one');

        $this->assertTrue($three->relationLoaded('two'));
        $this->assertTrue($three->relationLoaded('two.one'));

        $three->setRelations(['a' => ['x', 'y', 'z']]);

        $this->assertFalse($three->relationLoaded('two'));
        $this->assertFalse($three->relationLoaded('two.one'));
        $this->assertNull($three->getAttribute('a.z'));
        $this->assertTrue($three->relationLoaded('a'));
        $this->assertIsArray($three->getRelationValue('a'));
    }

    public function testWhenSetRelationsWithDotsInRelationNames()
    {
        $one = One::query()->create();

        $one->setRelations(['foo.bar' => ['x', 'y', 'z']]);

        $this->assertNotNull($one->getRelationValue('foo.bar'));
    }

    public function testGetRelationValue()
    {
        $one = One::query()->create();
        $two = $one->twos()->create();
        $two->threes()->create();
        $one->load('twos.threes');

        $this->assertNotNull($one->getAttribute('twos'));
        $this->assertNull($one->getRelationValue('twos.threes'));
    }
}

class One extends Model
{
    public $table = 'ones';
    public $timestamps = false;
    protected $guarded = [];

    public function twos(): HasMany
    {
        return $this->hasMany(Two::class, 'one_id');
    }
}

class Two extends Model
{
    public $table = 'twos';
    public $timestamps = false;
    protected $guarded = [];

    public function one(): BelongsTo
    {
        return $this->belongsTo(One::class, 'one_id');
    }

    public function threes(): HasMany
    {
        return $this->hasMany(Three::class, 'two_id');
    }
}

class Three extends Model
{
    public $table = 'threes';
    public $timestamps = false;
    protected $guarded = [];

    public function one(): BelongsTo
    {
        return $this->belongsTo(One::class, 'one_id');
    }

    public function two(): BelongsTo
    {
        return $this->belongsTo(Two::class, 'two_id');
    }
}
