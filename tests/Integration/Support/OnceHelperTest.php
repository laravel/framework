<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;

#[WithMigration]
class OnceHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase()
    {
        UserFactory::times(3)->create();
        UserFactory::times(2)->unverified()->create();
    }

    public function testItCanCacheStaticMethodWithoutParameters()
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $verifiedUsers = User::verified();
        $unverifiedUsers = User::unverified();

        $this->assertCount(3, $verifiedUsers);
        $this->assertCount(2, $unverifiedUsers);
        $this->assertCount(2, DB::getQueryLog());

        $verifiedUsers2 = User::verified();

        $this->assertCount(2, DB::getQueryLog());

        $this->assertSame($verifiedUsers, $verifiedUsers2);

        DB::disableQueryLog();
    }

    public function testItCanCacheStaticMethodWithParameters()
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $verifiedUsers = User::getByType('verified');
        $unverifiedUsers = User::getByType('unverified');

        $this->assertCount(3, $verifiedUsers);
        $this->assertCount(2, $unverifiedUsers);
        $this->assertCount(2, DB::getQueryLog());

        $verifiedUsers2 = User::getByType('verified');

        $this->assertCount(2, DB::getQueryLog());

        $this->assertSame($verifiedUsers, $verifiedUsers2);

        DB::disableQueryLog();
    }
}

class User extends Authenticatable
{
    public static function verified(): Collection
    {
        return once(fn () => self::whereNotNull('email_verified_at')->get());
    }

    public static function unverified(): Collection
    {
        return once(fn () => self::whereNull('email_verified_at')->get());
    }

    public static function getByType(string $type): Collection
    {
        return once(function () use ($type) {
            return match ($type) {
                'verified' => self::whereNotNull('email_verified_at')->get(),
                'unverified' => self::whereNull('email_verified_at')->get()
            };
        });
    }
}
