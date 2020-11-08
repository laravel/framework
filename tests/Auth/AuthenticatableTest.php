<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\AuthManager;
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
        $user = new class extends User {
            public function getRememberTokenName()
            {
                return '';
            }
        };
        $user->setRememberToken(true);
        $this->assertNull($user->getRememberToken());
    }

    public function testCustomDriverCreateList()
    {
        $authManager = new AuthManager(app());
        $authManager->provider('test', function (){});
        $authManager->provider('test2', function (){});

        $this->assertContains('test', $authManager->getCustomProviderCreators());
        $this->assertContains('test2', $authManager->getCustomProviderCreators());
        $this->assertNotContains('test3', $authManager->getCustomProviderCreators());
    }
}
