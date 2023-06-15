<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;

class AuthMustVerifyEmailTraitTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @return void
     */
    public function testWillConfirmEmailVerifiedAtColumn(): void
    {
        $user = Mockery::mock(User::class);
        $user->makePartial();
        $this->assertEquals('email_verified_at', $user->getEmailVerifiedAtColumn());
    }

    /**
     * @return void
     */
    public function testWillConfirmCustomEmailVerifiedAtColumn(): void
    {
        $user = Mockery::mock(CustomUser::class);
        $user->makePartial();
        $this->assertEquals('custom_email_verified_at', $user->getEmailVerifiedAtColumn());
    }

    /**
     * @return void
     */
    public function testWillHasVerifiedEmail(): void
    {
        $user = Mockery::mock(User::class);
        $user->makePartial();
        $user->{$user->getEmailVerifiedAtColumn()} = Carbon::now();
        $this->assertTrue($user->hasVerifiedEmail());
    }

    /**
     * @return void
     */
    public function testWillUpdateEmailVerifiedAtWhenMarkEmailAsVerified(): void
    {
        $user = Mockery::mock(User::class);
        $user->makePartial();
        $user->shouldReceive('forceFill')->withAnyArgs()->once()->andReturn(tap($user, function () use ($user) {
            $user->{$user->getEmailVerifiedAtColumn()} = $user->freshTimestamp();

            return $user;
        }));
        $user->shouldReceive('save')->once();
        $user->markEmailAsVerified();
        $this->assertInstanceOf(Carbon::class, $user->{$user->getEmailVerifiedAtColumn()});
    }
}

class User
{
    use MustVerifyEmail;

    public function save()
    {
        //
    }

    public function freshTimestamp(): Carbon
    {
        return Carbon::now();
    }
}

class CustomUser
{
    use MustVerifyEmail;

    public function getEmailVerifiedAtColumn(): string
    {
        return 'custom_email_verified_at';
    }
}
