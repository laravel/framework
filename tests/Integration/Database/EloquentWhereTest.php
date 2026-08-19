<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Relationships\WhereClauseUser;

class EloquentWhereTest extends DatabaseTestCase
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

    public function testWhereAndWhereOrBehavior()
    {
        /** @var \Illuminate\Tests\App\Models\Relationships\WhereClauseUser $firstUser */
        $firstUser = WhereClauseUser::create([
            'name' => 'test-name',
            'email' => 'test-email',
            'address' => 'test-address',
        ]);

        /** @var \Illuminate\Tests\App\Models\Relationships\WhereClauseUser $secondUser */
        $secondUser = WhereClauseUser::create([
            'name' => 'test-name1',
            'email' => 'test-email1',
            'address' => 'test-address1',
        ]);

        $this->assertTrue($firstUser->is(WhereClauseUser::where('name', '=', $firstUser->name)->first()));
        $this->assertTrue($firstUser->is(WhereClauseUser::where('name', $firstUser->name)->first()));
        $this->assertTrue($firstUser->is(WhereClauseUser::where('name', $firstUser->name)->where('email', $firstUser->email)->first()));
        $this->assertNull(WhereClauseUser::where('name', $firstUser->name)->where('email', $secondUser->email)->first());
        $this->assertTrue($secondUser->is(WhereClauseUser::where('name', 'wrong-name')->orWhere('email', $secondUser->email)->first()));
        $this->assertTrue($firstUser->is(WhereClauseUser::where(['name' => 'test-name', 'email' => 'test-email'])->first()));
        $this->assertNull(WhereClauseUser::where(['name' => 'test-name', 'email' => 'test-email1'])->first());
        $this->assertTrue($secondUser->is(
            WhereClauseUser::where(['name' => 'wrong-name', 'email' => 'test-email1'], null, null, 'or')->first())
        );

        $this->assertSame(
            1,
            WhereClauseUser::where(['name' => 'test-name', 'email' => 'test-email1'])
                ->orWhere(['name' => 'test-name1', 'address' => 'wrong-address'])->count()
        );

        $this->assertTrue(
            $secondUser->is(
                WhereClauseUser::where(['name' => 'test-name', 'email' => 'test-email1'])
                    ->orWhere(['name' => 'test-name1', 'address' => 'wrong-address'])
                    ->first()
            )
        );
    }

    public function testWhereNot()
    {
        /** @var \Illuminate\Tests\App\Models\Relationships\WhereClauseUser $firstUser */
        $firstUser = WhereClauseUser::create([
            'name' => 'test-name',
            'email' => 'test-email',
            'address' => 'test-address',
        ]);

        /** @var \Illuminate\Tests\App\Models\Relationships\WhereClauseUser $secondUser */
        $secondUser = WhereClauseUser::create([
            'name' => 'test-name1',
            'email' => 'test-email1',
            'address' => 'test-address1',
        ]);

        $this->assertTrue($secondUser->is(WhereClauseUser::whereNot(function ($query) use ($firstUser) {
            $query->where('name', '=', $firstUser->name);
        })->first()));
        $this->assertTrue($firstUser->is(WhereClauseUser::where('name', $firstUser->name)->whereNot(function ($query) use ($secondUser) {
            $query->where('email', $secondUser->email);
        })->first()));
        $this->assertTrue($secondUser->is(WhereClauseUser::where('name', 'wrong-name')->orWhereNot(function ($query) use ($firstUser) {
            $query->where('email', $firstUser->email);
        })->first()));
    }

    public function testWhereIn()
    {
        /** @var \Illuminate\Tests\App\Models\Relationships\WhereClauseUser $user1 */
        $user1 = WhereClauseUser::create([
            'name' => 'test-name1',
            'email' => 'test-email1',
            'address' => 'test-address1',
        ]);

        /** @var \Illuminate\Tests\App\Models\Relationships\WhereClauseUser $user2 */
        $user2 = WhereClauseUser::create([
            'name' => 'test-name2',
            'email' => 'test-email2',
            'address' => 'test-address2',
        ]);

        /** @var \Illuminate\Tests\App\Models\Relationships\WhereClauseUser $user3 */
        $user3 = WhereClauseUser::create([
            'name' => 'test-name2',
            'email' => 'test-email3',
            'address' => 'test-address3',
        ]);

        $this->assertTrue($user2->is(WhereClauseUser::whereIn('id', [2])->first()));

        $users = WhereClauseUser::query()->whereIn('id', [1, 2, 22])->get();

        $this->assertTrue($user1->is($users[0]));
        $this->assertTrue($user2->is($users[1]));
        $this->assertCount(2, $users);

        $users = WhereClauseUser::query()->whereIn('email', ['test-email1', 'test-email2'])->get();

        $this->assertTrue($user1->is($users[0]));
        $this->assertTrue($user2->is($users[1]));
        $this->assertCount(2, $users);

        $users = WhereClauseUser::query()
            ->whereIn('id', [1])
            ->orWhereIn('email', ['test-email1', 'test-email2'])
            ->get();

        $this->assertTrue($user1->is($users[0]));
        $this->assertTrue($user2->is($users[1]));
        $this->assertCount(2, $users);
    }

    public function testWhereInCanAcceptQueryable()
    {
        $user1 = WhereClauseUser::create([
            'name' => 'test-name1',
            'email' => 'test-email1',
            'address' => 'test-address1',
        ]);

        $user2 = WhereClauseUser::create([
            'name' => 'test-name2',
            'email' => 'test-email2',
            'address' => 'test-address2',
        ]);

        $user3 = WhereClauseUser::create([
            'name' => 'test-name2',
            'email' => 'test-email3',
            'address' => 'test-address3',
        ]);

        $query = WhereClauseUser::query()->select('name')->where('id', '>', 1);

        $users = WhereClauseUser::query()->whereIn('name', $query)->get();

        $this->assertTrue($user2->is($users[0]));
        $this->assertTrue($user3->is($users[1]));
        $this->assertCount(2, $users);

        $users = WhereClauseUser::query()->whereIn('name', function (Builder $query) {
            $query->select('name')->where('id', '>', 1);
        })->get();

        $this->assertTrue($user2->is($users[0]));
        $this->assertTrue($user3->is($users[1]));
        $this->assertCount(2, $users);

        $query = DB::table('users')->select('name')->where('id', '=', 1);

        $users = WhereClauseUser::query()->whereNotIn('name', $query)->get();

        $this->assertTrue($user2->is($users[0]));
        $this->assertTrue($user3->is($users[1]));
        $this->assertCount(2, $users);
    }

    public function testWhereIntegerInRaw()
    {
        /** @var \Illuminate\Tests\App\Models\Relationships\WhereClauseUser $user1 */
        $user1 = WhereClauseUser::create([
            'name' => 'test-name1',
            'email' => 'test-email1',
            'address' => 'test-address1',
        ]);

        /** @var \Illuminate\Tests\App\Models\Relationships\WhereClauseUser $user2 */
        $user2 = WhereClauseUser::create([
            'name' => 'test-name2',
            'email' => 'test-email2',
            'address' => 'test-address2',
        ]);

        /** @var \Illuminate\Tests\App\Models\Relationships\WhereClauseUser $user3 */
        $user3 = WhereClauseUser::create([
            'name' => 'test-name2',
            'email' => 'test-email3',
            'address' => 'test-address3',
        ]);

        $users = WhereClauseUser::query()->whereIntegerInRaw('id', [1, 2, 5])->get();
        $this->assertTrue($user1->is($users[0]));
        $this->assertTrue($user2->is($users[1]));
        $this->assertCount(2, $users);

        $users = WhereClauseUser::query()->whereIntegerNotInRaw('id', [2])->get();
        $this->assertTrue($user1->is($users[0]));
        $this->assertTrue($user3->is($users[1]));
        $this->assertCount(2, $users);

        $users = WhereClauseUser::query()->whereIntegerInRaw('id', ['1', '2'])->get();
        $this->assertTrue($user1->is($users[0]));
        $this->assertTrue($user2->is($users[1]));
        $this->assertCount(2, $users);
    }

    public function testFirstWhere()
    {
        /** @var \Illuminate\Tests\App\Models\Relationships\WhereClauseUser $firstUser */
        $firstUser = WhereClauseUser::create([
            'name' => 'test-name',
            'email' => 'test-email',
            'address' => 'test-address',
        ]);

        /** @var \Illuminate\Tests\App\Models\Relationships\WhereClauseUser $secondUser */
        $secondUser = WhereClauseUser::create([
            'name' => 'test-name1',
            'email' => 'test-email1',
            'address' => 'test-address1',
        ]);

        $this->assertTrue($firstUser->is(WhereClauseUser::firstWhere('name', '=', $firstUser->name)));
        $this->assertTrue($firstUser->is(WhereClauseUser::firstWhere('name', $firstUser->name)));
        $this->assertTrue($firstUser->is(WhereClauseUser::where('name', $firstUser->name)->firstWhere('email', $firstUser->email)));
        $this->assertNull(WhereClauseUser::where('name', $firstUser->name)->firstWhere('email', $secondUser->email));
        $this->assertTrue($firstUser->is(WhereClauseUser::firstWhere(['name' => 'test-name', 'email' => 'test-email'])));
        $this->assertNull(WhereClauseUser::firstWhere(['name' => 'test-name', 'email' => 'test-email1']));
        $this->assertTrue($secondUser->is(
            WhereClauseUser::firstWhere(['name' => 'wrong-name', 'email' => 'test-email1'], null, null, 'or'))
        );
    }

    public function testSole()
    {
        $expected = WhereClauseUser::create([
            'name' => 'test-name',
            'email' => 'test-email',
            'address' => 'test-address',
        ]);

        $this->assertTrue($expected->is(WhereClauseUser::where('name', 'test-name')->sole()));
    }

    public function testSoleFailsForMultipleRecords()
    {
        WhereClauseUser::create([
            'name' => 'test-name',
            'email' => 'test-email',
            'address' => 'test-address',
        ]);

        WhereClauseUser::create([
            'name' => 'test-name',
            'email' => 'other-email',
            'address' => 'other-address',
        ]);

        $this->expectExceptionObject(new MultipleRecordsFoundException(2));

        WhereClauseUser::where('name', 'test-name')->sole();
    }

    public function testSoleFailsIfNoRecords()
    {
        try {
            WhereClauseUser::where('name', 'test-name')->sole();
        } catch (ModelNotFoundException $exception) {
            //
        }

        $this->assertSame(WhereClauseUser::class, $exception->getModel());
    }

    public function testSoleValue()
    {
        $expected = WhereClauseUser::create([
            'name' => 'test-name',
            'email' => 'test-email',
            'address' => 'test-address',
        ]);

        $this->assertSame('test-name', WhereClauseUser::where('name', 'test-name')->soleValue('name'));
    }

    public function testChunkMap()
    {
        WhereClauseUser::create([
            'name' => 'first-name',
            'email' => 'first-email',
            'address' => 'first-address',
        ]);

        WhereClauseUser::create([
            'name' => 'second-name',
            'email' => 'second-email',
            'address' => 'second-address',
        ]);

        DB::enableQueryLog();

        $results = WhereClauseUser::orderBy('id')->chunkMap(function ($user) {
            return $user->name;
        }, 1);

        $this->assertCount(2, $results);
        $this->assertSame('first-name', $results[0]);
        $this->assertSame('second-name', $results[1]);
        $this->assertCount(3, DB::getQueryLog());
    }
}
