<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Foundation\Auth\User;
use PHPUnit\Framework\TestCase;

class AuthenticatableTest extends TestCase
{
    public function testItReturnsSameRememberTokenForString()
    {
        $user = new User;
        $user->setRememberToken('sample_token');
        $this->assertSame('sample_token', $user->getRememberToken());
    }

    public function testItReturnsStringAsRememberTokenWhenItWasSetToTrue()
    {
        $user = new User;
        $user->setRememberToken(true);
        $this->assertSame('1', $user->getRememberToken());
    }

    public function testItReturnsNullWhenRememberTokenNameWasSetToEmpty()
    {
        $user = new class extends User
        {
            public function getRememberTokenName()
            {
                return '';
            }
        };
        $user->setRememberToken(true);
        $this->assertNull($user->getRememberToken());
    }
}
