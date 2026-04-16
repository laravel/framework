<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\Fixtures\User;

class EloquentCollectionRefreshTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->timestamps();
        });
    }

    public function testRefreshMutatesExistingModelInstances()
    {
        User::insert([
            ['email' => 'taylor@laravel.com'],
            ['email' => 'mohamed@laravel.com'],
        ]);

        $collection = User::all();
        $first = $collection->first();

        User::where('id', $first->id)->update(['email' => 'updated@laravel.com']);

        $collection->refresh();

        $this->assertSame($first, $collection->first());
        $this->assertSame('updated@laravel.com', $first->email);
    }

    public function testRefreshSyncsOriginal()
    {
        User::insert([
            ['email' => 'taylor@laravel.com'],
        ]);

        $collection = User::all();

        User::where('id', $collection->first()->id)->update(['email' => 'updated@laravel.com']);

        $collection->refresh();

        $this->assertEmpty($collection->first()->getDirty());
        $this->assertSame('updated@laravel.com', $collection->first()->getOriginal('email'));
    }

    public function testRefreshNullsDeletedModels()
    {
        User::insert([
            ['email' => 'taylor@laravel.com'],
            ['email' => 'mohamed@laravel.com'],
        ]);

        $collection = User::all();

        $collection->first()->delete();

        $collection->refresh();

        $this->assertNull($collection->first());
        $this->assertCount(2, $collection);
    }

    public function testRefreshReturnsEmptyCollectionWhenEmpty()
    {
        $collection = new EloquentCollection;

        $result = $collection->refresh();

        $this->assertSame($collection, $result);
        $this->assertCount(0, $result);
    }

    public function testRefreshReturnsSelf()
    {
        User::insert([
            ['email' => 'taylor@laravel.com'],
        ]);

        $collection = User::all();
        $result = $collection->refresh();

        $this->assertSame($collection, $result);
    }
}
