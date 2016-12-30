<?php

use Mockery as m;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Auth\PasswordBroker;

class AuthPasswordBrokerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testIfUserIsNotFoundErrorRedirectIsReturned()
    {
        $mocks = $this->getMocks();
        $broker = $this->getMockBuilder('Illuminate\Auth\Passwords\PasswordBroker')->setMethods(['getUser', 'makeErrorRedirect'])->setConstructorArgs(array_values($mocks))->getMock();
        $broker->expects($this->once())->method('getUser')->will($this->returnValue(null));

        $this->assertEquals(PasswordBroker::INVALID_USER, $broker->sendResetLink(['credentials']));
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testGetUserThrowsExceptionIfUserDoesntImplementCanResetPassword()
    {
        $creds = ['email' => 'taylor@laravel.com'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn('foo');

        $broker->getUser($creds);
    }

    public function testUserIsRetrievedByCredentials()
    {
        $creds = ['email' => 'taylor@laravel.com'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user = m::mock('Illuminate\Contracts\Auth\CanResetPassword'));

        $this->assertEquals($user, $broker->getUser($creds));
    }

    public function testBrokerCreatesTokenAndRedirectsWithoutError()
    {
        $creds = ['email' => 'taylor@laravel.com'];
        $mocks = $this->getMocks();
        $broker = $this->getMockBuilder('Illuminate\Auth\Passwords\PasswordBroker')->setMethods(['emailResetLink', 'getKey'])->setConstructorArgs(array_values($mocks))->getMock();

        $user = m::mock('Illuminate\Contracts\Auth\CanResetPassword');
        $user->password = 'foo';
        $user->updated_at = Carbon::now();
        $user->shouldReceive('getEmailForPasswordReset')->once();
        $user->shouldReceive('getKey')->once();

        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user);

        $callback = function () {
            //
        };
        $user->shouldReceive('sendPasswordResetNotification');

        $this->assertEquals(PasswordBroker::RESET_LINK_SENT, $broker->sendResetLink($creds, $callback));
    }

    public function testRedirectIsReturnedByResetWhenUserCredentialsInvalid()
    {
        $creds = ['email' => 'taylor@laravel.com'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn(null);

        $this->assertEquals(PasswordBroker::INVALID_USER, $broker->reset($creds, function () {
            //
        }));
    }

    public function testRedirectReturnedByRemindWhenPasswordsDontMatch()
    {
        $creds = ['email' => 'taylor@laravel.com', 'password' => 'foo', 'password_confirmation' => 'bar'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['email' => 'taylor@laravel.com'])->andReturn($user = m::mock('Illuminate\Contracts\Auth\CanResetPassword'));

        $this->assertEquals(PasswordBroker::INVALID_PASSWORD, $broker->reset($creds, function () {
            //
        }));
    }

    public function testRedirectReturnedByRemindWhenPasswordNotSet()
    {
        $creds = ['email' => 'taylor@laravel.com', 'password' => null, 'password_confirmation' => null];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['email' => 'taylor@laravel.com'])->andReturn($user = m::mock('Illuminate\Contracts\Auth\CanResetPassword'));

        $this->assertEquals(PasswordBroker::INVALID_PASSWORD, $broker->reset($creds, function () {
            //
        }));
    }

    public function testRedirectReturnedByRemindWhenPasswordsLessThanSixCharacters()
    {
        $creds = ['email' => 'taylor@laravel.com', 'password' => 'abc', 'password_confirmation' => 'abc'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['email' => 'taylor@laravel.com'])->andReturn($user = m::mock('Illuminate\Contracts\Auth\CanResetPassword'));

        $this->assertEquals(PasswordBroker::INVALID_PASSWORD, $broker->reset($creds, function () {
            //
        }));
    }

    public function testRedirectReturnedByRemindWhenPasswordDoesntPassValidator()
    {
        $creds = ['email' => 'taylor@laravel.com', 'password' => 'abcdef', 'password_confirmation' => 'abcdef'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $broker->validator(function ($credentials) {
            return strlen($credentials['password']) >= 7;
        });
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['email' => 'taylor@laravel.com'])->andReturn($user = m::mock('Illuminate\Contracts\Auth\CanResetPassword'));

        $this->assertEquals(PasswordBroker::INVALID_PASSWORD, $broker->reset($creds, function () {
            //
        }));
    }

    protected function getBroker($mocks)
    {
        return new Illuminate\Auth\Passwords\PasswordBroker($mocks['app'], $mocks['users'], $mocks['expiration']);
    }

    protected function getMocks()
    {
        $mocks = [
            'app' => m::mock('Illuminate\Contracts\Foundation\Application'),
            'users' => m::mock('Illuminate\Contracts\Auth\UserProvider'),
            'expiration' => time(),
        ];

        return $mocks;
    }
}
