<?php

namespace Illuminate\Tests\Integration\Database\EloquentWithCountTest;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentWithCountTest extends DatabaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('one', function ($table) {
            $table->increments('id');
        });

        Schema::create('two', function ($table) {
            $table->increments('id');
            $table->integer('one_id');
        });

        Schema::create('three', function ($table) {
            $table->increments('id');
            $table->integer('two_id');
        });

        Schema::create('four', function ($table) {
            $table->increments('id');
            $table->integer('one_id');
        });
    }

    /**
     * @test
     */
    public function it_basic()
    {
        $one = Model1::create();
        $two = $one->twos()->Create();
        $three = $two->threes()->Create();

        $results = Model1::withCount([
            'twos' => function ($query) {
                $query->where('id', '>=', 1);
            },
        ]);

        $this->assertEquals([
            ['id' => 1, 'twos_count' => 1],
        ], $results->get()->toArray());
    }

    public function test_global_scopes()
    {
        $one = Model1::create();
        $one->fours()->create();

        $result = Model1::withCount('fours')->first();
        $this->assertEquals(0, $result->fours_count);

        $result = Model1::withCount('allFours')->first();
        $this->assertEquals(1, $result->all_fours_count);
    }
}

class Model1 extends Model
{
    public $table = 'one';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function twos()
    {
        return $this->hasMany(Model2::class, 'one_id');
    }

    public function fours()
    {
        return $this->hasMany(Model4::class, 'one_id');
    }

    public function allFours()
    {
        return $this->fours()->withoutGlobalScopes();
    }
}

class Model2 extends Model
{
    public $table = 'two';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $withCount = ['threes'];

    public function threes()
    {
        return $this->hasMany(Model3::class, 'two_id');
    }
}

class Model3 extends Model
{
    public $table = 'three';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('app', function ($builder) {
            $builder->where('idz', '>', 0);
        });
    }
}

class Model4 extends Model
{
    public $table = 'four';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('app', function ($builder) {
            $builder->where('id', '>', 1);
        });
    }
}
