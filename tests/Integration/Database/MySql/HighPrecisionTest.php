<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\EloquentCollectionLoadMissingTest\Post;

/**
 * @requires extension pdo_mysql
 * @requires OS Linux|Darwin
 */
class HighPrecisionTest extends MySqlTestCase
{
    public function testWhereBetweenOnHighPrecisionColumn()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps(6);
        });

        User::create(['id' => 1, 'created_at' => today()->subDay()]);
        User::create(['id' => 2, 'created_at' => today()]);
        User::create(['id' => 3, 'created_at' => now()]);
        User::create(['id' => 4, 'created_at' => today()->endOfDay()]);
        User::create(['id' => 5, 'created_at' => today()->addDay()]);

        $this->assertSame(
            [2, 3, 4],
            User::whereBetween('created_at', [
                today(),
                today()->endOfDay(),
            ])->get()->modelKeys(),
        );

        Schema::drop('users');
    }

    public function testCreateOnHighPrecisionColumn()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps(6);
        });

        Carbon::setTestNow('2022-01-01 00:00:00.000000');
        User::create();

        Carbon::setTestNow('2022-01-01 23:59:59.999999');
        User::create();

        $this->assertSame(
            [
                '2022-01-01 00:00:00.000000',
                '2022-01-01 23:59:59.999999',
            ],
            User::all(['created_at'])->map(fn ($u) => $u->getRawOriginal('created_at'))->toArray(),
        );

        Schema::drop('users');
    }

    public function testWhereBetweenOnDefaultPrecisionColumn()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Message::create(['id' => 1, 'created_at' => today()->subDay()]);
        Message::create(['id' => 2, 'created_at' => today()]);
        Message::create(['id' => 3, 'created_at' => now()]);
        Message::create(['id' => 4, 'created_at' => today()->endOfDay()]);
        Message::create(['id' => 5, 'created_at' => today()->addDay()]);

        $this->assertSame(
            [2, 3, 4],
            Message::whereBetween('created_at', [
                today(),
                today()->endOfDay(),
            ])->get()->modelKeys(),
        );

        Schema::drop('messages');
    }

    public function testCreateOnDefaultPrecisionColumn()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Carbon::setTestNow('2022-01-01 00:00:00.000000');
        Message::create();

        Carbon::setTestNow('2022-01-01 23:59:59.999999');
        Message::create();

        $this->assertSame(
            [
                '2022-01-01 00:00:00',
                '2022-01-01 23:59:59',
            ],
            Message::all(['created_at'])->map(fn ($u) => $u->getRawOriginal('created_at'))->toArray(),
        );

        Schema::drop('messages');
    }
}

class User extends Model
{
    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = [];
}

class Message extends Model
{
    protected $guarded = [];
}
