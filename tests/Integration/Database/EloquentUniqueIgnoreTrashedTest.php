<?php

namespace Illuminate\Tests\Integration\Database\EloquentUniqueIgnoreTrashedTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentUniqueIgnoreTrashedTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->string('username')->uniqueIgnoreTrashed();
        });
    }

    public function classes()
    {
        return [
            [UserWithSoftDeletes::class],
            [UserWithoutSoftDeletes::class],
        ];
    }

    /**
     * @group SkipMSSQL
     * @dataProvider classes
     */
    public function testUniqueAllowsDuplicatesIfDeleted($class)
    {
        $user = new $class();
        $user->username = 'morrislaptop';
        $user->save();
        $user->delete();

        $user2 = new $class();
        $user2->username = 'morrislaptop';
        $user2->save();

        $this->assertCount(1, $class::all());
    }

    /**
     * @group SkipMSSQL
     * @dataProvider classes
     */
    public function testUniqueRejectsDuplicates($class)
    {
        $user = new $class();
        $user->username = 'morrislaptop';
        $user->save();

        $user2 = new $class();
        $user2->username = 'morrislaptop';
        $this->expectException(QueryException::class);
        $user2->save();
    }

    /**
     * @group SkipMSSQL
     * @dataProvider classes
     */
    public function testUniqueRejectsDuplicatesWithDeleted($class)
    {
        $user = new $class();
        $user->username = 'morrislaptop';
        $user->save();
        $user->delete();

        $user2 = new $class();
        $user2->username = 'morrislaptop';
        $user2->save();

        $user3 = new $class();
        $user3->username = 'morrislaptop';
        $this->expectException(QueryException::class);
        $user3->save();
    }
}

class UserWithSoftDeletes extends Model
{
    use SoftDeletes;
    protected $table = 'users';
}

class UserWithoutSoftDeletes extends Model
{
    protected $table = 'users';
}
