<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AfterQueryTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->string('address');
        });
    }

    public function testAfterQueryOnEloquentBuilder()
    {
        UserAfterQueryTest::create([
            'name' => 'test-name-1',
            'email' => 'test-email-1',
            'address' => 'test-address-1',
        ]);
        UserAfterQueryTest::create([
            'name' => 'test-name-2',
            'email' => 'test-email-2',
            'address' => 'test-address-2',
        ]);

        $afterQueryIds = collect();

        $users = UserAfterQueryTest::query()
            ->afterQuery(function (Collection $users) use ($afterQueryIds) {
                $afterQueryIds->push(...$users->pluck('id')->all());

                foreach ($users as $user) {
                    $this->assertInstanceOf(UserAfterQueryTest::class, $user);
                }
            })
            ->get();

        $this->assertCount(2, $users);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $users->pluck('id')->toArray());
    }

    public function testAfterQueryOnBaseBuilder()
    {
        UserAfterQueryTest::create([
            'name' => 'test-name-1',
            'email' => 'test-email-1',
            'address' => 'test-address-1',
        ]);
        UserAfterQueryTest::create([
            'name' => 'test-name-2',
            'email' => 'test-email-2',
            'address' => 'test-address-2',
        ]);

        $afterQueryIds = collect();

        $users = UserAfterQueryTest::query()
            ->toBase()
            ->afterQuery(function (Collection $users) use ($afterQueryIds) {
                $afterQueryIds->push(...$users->pluck('id')->all());

                foreach ($users as $user) {
                    $this->assertNotInstanceOf(UserAfterQueryTest::class, $user);
                }
            })
            ->get();

        $this->assertCount(2, $users);
        $this->assertEqualsCanonicalizing($afterQueryIds->toArray(), $users->pluck('id')->toArray());
    }
}

class UserAfterQueryTest extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;
}
