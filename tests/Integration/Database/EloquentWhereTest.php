<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentWhereTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('address');
        });
    }

    public function testWhereAndWhereOrBehavior()
    {
        /** @var \Illuminate\Tests\Integration\Database\UserWhereTest $firstUser */
        $firstUser = UserWhereTest::create([
            'name' => 'test-name',
            'email' => 'test-email',
            'address' => 'test-address',
        ]);

        /** @var \Illuminate\Tests\Integration\Database\UserWhereTest $secondUser */
        $secondUser = UserWhereTest::create([
            'name' => 'test-name1',
            'email' => 'test-email1',
            'address' => 'test-address1',
        ]);

        $this->assertTrue($firstUser->is(UserWhereTest::where('name', '=', $firstUser->name)->first()));
        $this->assertTrue($firstUser->is(UserWhereTest::where('name', $firstUser->name)->first()));
        $this->assertTrue($firstUser->is(UserWhereTest::where('name', $firstUser->name)->where('email', $firstUser->email)->first()));
        $this->assertNull(UserWhereTest::where('name', $firstUser->name)->where('email', $secondUser->email)->first());
        $this->assertTrue($secondUser->is(UserWhereTest::where('name', 'wrong-name')->orWhere('email', $secondUser->email)->first()));
        $this->assertTrue($firstUser->is(UserWhereTest::where(['name' => 'test-name', 'email' => 'test-email'])->first()));
        $this->assertNull(UserWhereTest::where(['name' => 'test-name', 'email' => 'test-email1'])->first());
        $this->assertTrue($secondUser->is(
            UserWhereTest::where(['name' => 'wrong-name', 'email' => 'test-email1'], null, null, 'or')->first())
        );

        $this->assertSame(
            1,
            UserWhereTest::where(['name' => 'test-name', 'email' => 'test-email1'])
                ->orWhere(['name' => 'test-name1', 'address' => 'wrong-address'])->count()
        );

        $this->assertTrue(
            $secondUser->is(
                UserWhereTest::where(['name' => 'test-name', 'email' => 'test-email1'])
                    ->orWhere(['name' => 'test-name1', 'address' => 'wrong-address'])
                    ->first()
            )
        );
    }

    public function testFirstWhere()
    {
        /** @var \Illuminate\Tests\Integration\Database\UserWhereTest $firstUser */
        $firstUser = UserWhereTest::create([
            'name' => 'test-name',
            'email' => 'test-email',
            'address' => 'test-address',
        ]);

        /** @var \Illuminate\Tests\Integration\Database\UserWhereTest $secondUser */
        $secondUser = UserWhereTest::create([
            'name' => 'test-name1',
            'email' => 'test-email1',
            'address' => 'test-address1',
        ]);

        $this->assertTrue($firstUser->is(UserWhereTest::firstWhere('name', '=', $firstUser->name)));
        $this->assertTrue($firstUser->is(UserWhereTest::firstWhere('name', $firstUser->name)));
        $this->assertTrue($firstUser->is(UserWhereTest::where('name', $firstUser->name)->firstWhere('email', $firstUser->email)));
        $this->assertNull(UserWhereTest::where('name', $firstUser->name)->firstWhere('email', $secondUser->email));
        $this->assertTrue($firstUser->is(UserWhereTest::firstWhere(['name' => 'test-name', 'email' => 'test-email'])));
        $this->assertNull(UserWhereTest::firstWhere(['name' => 'test-name', 'email' => 'test-email1']));
        $this->assertTrue($secondUser->is(
            UserWhereTest::firstWhere(['name' => 'wrong-name', 'email' => 'test-email1'], null, null, 'or'))
        );
    }

    public function testSole()
    {
        $expected = UserWhereTest::create([
            'name' => 'test-name',
            'email' => 'test-email',
            'address' => 'test-address',
        ]);

        $this->assertTrue($expected->is(UserWhereTest::where('name', 'test-name')->sole()));
    }

    public function testSoleFailsForMultipleRecords()
    {
        UserWhereTest::create([
            'name' => 'test-name',
            'email' => 'test-email',
            'address' => 'test-address',
        ]);

        UserWhereTest::create([
            'name' => 'test-name',
            'email' => 'other-email',
            'address' => 'other-address',
        ]);

        $this->expectException(MultipleRecordsFoundException::class);

        UserWhereTest::where('name', 'test-name')->sole();
    }

    public function testSoleFailsIfNoRecords()
    {
        try {
            UserWhereTest::where('name', 'test-name')->sole();
        } catch (ModelNotFoundException $exception) {
            //
        }

        $this->assertSame(UserWhereTest::class, $exception->getModel());
    }

    public function testChunkMap()
    {
        UserWhereTest::create([
            'name' => 'first-name',
            'email' => 'first-email',
            'address' => 'first-address',
        ]);

        UserWhereTest::create([
            'name' => 'second-name',
            'email' => 'second-email',
            'address' => 'second-address',
        ]);

        DB::enableQueryLog();

        $results = UserWhereTest::orderBy('id')->chunkMap(function ($user) {
            return $user->name;
        }, 1);

        $this->assertCount(2, $results);
        $this->assertSame('first-name', $results[0]);
        $this->assertSame('second-name', $results[1]);
        $this->assertCount(3, DB::getQueryLog());
    }
}

class UserWhereTest extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
}
