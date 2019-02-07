<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

/**
 * @group integration
 */
class EloquentWhereTest extends DatabaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('address');
        });
    }

    public function testWhereAndWhereOrBehavior()
    {
        /** @var UserWhereTest $firstUser */
        $firstUser = UserWhereTest::create([
            'name' => 'test-name',
            'email' => 'test-email',
            'address' => 'test-address',
        ]);

        /** @var UserWhereTest $secondUser */
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
}

class UserWhereTest extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
}
