<?php

namespace Illuminate\Tests\Database\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class EloquentWithDefaultEventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
        });

        Schema::create('test_wallets', function (Blueprint $table) {
            $table->id();
            $table->morphs('holder'); // holder_id + holder_type
            $table->integer('balance')->default(0);
        });
    }

    public function test_withDefault_creates_unsaved_related_model_in_event()
    {
        TestBusiness::creating(function ($business) {
            $wallet = $business->wallet;

            $this->assertInstanceOf(TestWallet::class, $wallet);
            $this->assertFalse($wallet->exists);
            $this->assertEquals(TestBusiness::class, $wallet->holder_type);
            $this->assertNull($wallet->holder_id);
        });

        TestBusiness::create(['name' => 'ACME Corp']);
    }
}

class TestBusiness extends Model
{
    protected $table = 'test_businesses';
    protected $fillable = ['name'];
    public $timestamps = false;

    protected static function booted()
    {
        static::automaticallyEagerLoadRelationships();
    }

    public function wallet(): MorphOne
    {
        return $this->morphOne(TestWallet::class, 'holder')->withDefault([
            'balance' => 100,
        ]);
    }
}

class TestWallet extends Model
{
    protected $table = 'test_wallets';
    protected $fillable = ['balance'];
    public $timestamps = false;

    public function holder()
    {
        return $this->morphTo();
    }
}