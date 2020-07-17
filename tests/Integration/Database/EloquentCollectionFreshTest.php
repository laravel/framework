<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\Fixtures\User;

/**
 * @group integration
 */
class EloquentCollectionFreshTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
        });
    }

    public function testEloquentCollectionFresh()
    {
        User::insert([
            ['email' => 'laravel@framework.com'],
            ['email' => 'laravel@laravel.com'],
        ]);

        $collection = User::all();

        User::whereKey($collection->pluck('id')->toArray())->delete();

        $this->assertEmpty($collection->fresh()->filter());
    }
}
