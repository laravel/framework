<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class DatabaseEloquentDefaultModelTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('businesses', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('wallets', function ($table) {
            $table->increments('id');
            $table->morphs('holder');
            $table->decimal('balance', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('transactions', function ($table) {
            $table->increments('id');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('type');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('businesses');
        parent::tearDown();
    }

    public function testDefaultModelHasProperForeignKeysDuringAutomaticEagerLoading()
    {
        Model::automaticallyEagerLoadRelationships();
        $business = Business::create();
        $wallet = $business->wallet;
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(0, $wallet->balance);
        $this->assertEquals($business->id, $wallet->holder_id);
        $this->assertEquals(Business::class, $wallet->holder_type);
        $wallet->balance = 100;
        $wallet->save();
        $this->assertDatabaseHas('wallets', [
            'holder_id' => $business->id,
            'holder_type' => Business::class,
            'balance' => 100,
        ]);
        Model::automaticallyEagerLoadRelationships(false);
    }

    public function testDefaultModelWithCallbackHasProperForeignKeys()
    {
        Model::automaticallyEagerLoadRelationships();
        $business = Business::create();
        $wallet = $business->walletWithCallback;
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(50, $wallet->balance);
        $this->assertEquals($business->id, $wallet->holder_id);
        $this->assertEquals(Business::class, $wallet->holder_type);
        $wallet->balance = 200;
        $wallet->save();
        $this->assertDatabaseHas('wallets', [
            'holder_id' => $business->id,
            'holder_type' => Business::class,
            'balance' => 200,
        ]);
        Model::automaticallyEagerLoadRelationships(false);
    }

    public function testDefaultModelInModelEvent()
    {
        Model::automaticallyEagerLoadRelationships();
        Business::created(function ($business) {
            $business->wallet->balance = 75;
            $business->wallet->save();
        });
        $business = Business::create();
        $this->assertDatabaseHas('wallets', [
            'holder_id' => $business->id,
            'holder_type' => Business::class,
            'balance' => 75,
        ]);
        Model::automaticallyEagerLoadRelationships(false);
    }

    public function testDefaultModelWithNestedRelationships()
    {
        Model::automaticallyEagerLoadRelationships();
        $business = Business::create();
        $wallet = $business->wallet;
        $wallet->save();
        $transaction = $wallet->transactions()->create([
            'amount' => 100,
            'type' => 'credit',
        ]);
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->id,
            'amount' => 100,
            'type' => 'credit',
        ]);
        Model::automaticallyEagerLoadRelationships(false);
    }

    public function testDefaultModelWithMultipleAccesses()
    {
        Model::automaticallyEagerLoadRelationships();
        $business = Business::create();
        $wallet1 = $business->wallet;
        $wallet2 = $business->wallet;
        $this->assertSame($wallet1, $wallet2);
        $this->assertEquals($business->id, $wallet1->holder_id);
        $this->assertEquals(Business::class, $wallet1->holder_type);
        Model::automaticallyEagerLoadRelationships(false);
    }
}

class Business extends Model
{
    protected $guarded = [];

    public static function booted()
    {
        static::automaticallyEagerLoadRelationships();
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'holder')
            ->withDefault([
                'balance' => 0,
            ]);
    }

    public function walletWithCallback()
    {
        return $this->morphOne(Wallet::class, 'holder')
            ->withDefault(function ($wallet) {
                $wallet->balance = 50;
            });
    }
}

class Wallet extends Model
{
    protected $guarded = [];

    public function holder()
    {
        return $this->morphTo();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}

class Transaction extends Model
{
    protected $guarded = [];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}