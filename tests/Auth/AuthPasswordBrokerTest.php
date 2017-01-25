<?php

namespace Illuminate\Tests\Auth;

use Mockery as m;
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
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['foo'])->andReturn('bar');

        $broker->getUser(['foo']);
    }

    public function testUserIsRetrievedByCredentials()
    {
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['foo'])->andReturn($user = m::mock('Illuminate\Contracts\Auth\CanResetPassword'));

        $this->assertEquals($user, $broker->getUser(['foo']));
    }

    public function testBrokerCreatesTokenAndRedirectsWithoutError()
    {
        $mocks = $this->getMocks();
        $broker = $this->getMockBuilder('Illuminate\Auth\Passwords\PasswordBroker')->setMethods(['emailResetLink', 'getUri'])->setConstructorArgs(array_values($mocks))->getMock();
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['foo'])->andReturn($user = m::mock('Illuminate\Contracts\Auth\CanResetPassword'));
        $mocks['tokens']->shouldReceive('create')->once()->with($user)->andReturn('token');
        $callback = function () {
        };
        $user->shouldReceive('sendPasswordResetNotification')->with('token');

        $this->assertEquals(PasswordBroker::RESET_LINK_SENT, $broker->sendResetLink(['foo'], $callback));
    }

    public function testRedirectIsReturnedByResetWhenUserCredentialsInvalid()
    {
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['creds'])->andReturn(null);

        $this->assertEquals(PasswordBroker::INVALID_USER, $broker->reset(['creds'], function () {
        }));
    }

    public function testRedirectReturnedByRemindWhenPasswordsDontMatch()
    {
        $creds = ['password' => 'foo', 'password_confirmation' => 'bar'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user = m::mock('Illuminate\Contracts\Auth\CanResetPassword'));

        $this->assertEquals(PasswordBroker::INVALID_PASSWORD, $broker->reset($creds, function () {
        }));
    }

    public function testRedirectReturnedByRemindWhenPasswordNotSet()
    {
        $creds = ['password' => null, 'password_confirmation' => null];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user = m::mock('Illuminate\Contracts\Auth\CanResetPassword'));

        $this->assertEquals(PasswordBroker::INVALID_PASSWORD, $broker->reset($creds, function () {
        }));
    }

    public function testRedirectReturnedByRemindWhenPasswordsLessThanSixCharacters()
    {
        $creds = ['password' => 'abc', 'password_confirmation' => 'abc'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user = m::mock('Illuminate\Contracts\Auth\CanResetPassword'));

        $this->assertEquals(PasswordBroker::INVALID_PASSWORD, $broker->reset($creds, function () {
        }));
    }

    public function testRedirectReturnedByRemindWhenPasswordDoesntPassValidator()
    {
        $creds = ['password' => 'abcdef', 'password_confirmation' => 'abcdef'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $broker->validator(function ($credentials) {
            return strlen($credentials['password']) >= 7;
        });
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user = m::mock('Illuminate\Contracts\Auth\CanResetPassword'));

        $this->assertEquals(PasswordBroker::INVALID_PASSWORD, $broker->reset($creds, function () {
        }));
    }

    public function testRedirectReturnedByRemindWhenRecordDoesntExistInTable()
    {
        $creds = ['token' => 'token'];
        $broker = $this->getMockBuilder('Illuminate\Auth\Passwords\PasswordBroker')->setMethods(['validateNewPassword'])->setConstructorArgs(array_values($mocks = $this->getMocks()))->getMock();
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(array_except($creds, ['token']))->andReturn($user = m::mock('Illuminate\Contracts\Auth\CanResetPassword'));
        $broker->expects($this->once())->method('validateNewPassword')->will($this->returnValue(true));
        $mocks['tokens']->shouldReceive('exists')->with($user, 'token')->andReturn(false);

        $this->assertEquals(PasswordBroker::INVALID_TOKEN, $broker->reset($creds, function () {
        }));
    }

    public function testResetRemovesRecordOnReminderTableAndCallsCallback()
    {
        unset($_SERVER['__password.reset.test']);
        $broker = $this->getMockBuilder('Illuminate\Auth\Passwords\PasswordBroker')->setMethods(['validateReset', 'getPassword', 'getToken'])->setConstructorArgs(array_values($mocks = $this->getMocks()))->getMock();
        $broker->expects($this->once())->method('validateReset')->will($this->returnValue($user = m::mock('Illuminate\Contracts\Auth\CanResetPassword')));
        $mocks['tokens']->shouldReceive('delete')->once()->with($user);
        $callback = function ($user, $password) {
            $_SERVER['__password.reset.test'] = compact('user', 'password');

            return 'foo';
        };

        $this->assertEquals(PasswordBroker::PASSWORD_RESET, $broker->reset(['password' => 'password', 'token' => 'token'], $callback));
        $this->assertEquals(['user' => $user, 'password' => 'password'], $_SERVER['__password.reset.test']);
    }

    protected function getBroker($mocks)
    {
        return new \Illuminate\Auth\Passwords\PasswordBroker($mocks['tokens'], $mocks['users'], $mocks['mailer'], $mocks['view']);
    }

    protected function getMocks()
    {
        $mocks = [
            'tokens' => m::mock('Illuminate\Auth\Passwords\TokenRepositoryInterface'),
            'users'  => m::mock('Illuminate\Contracts\Auth\UserProvider'),
            'mailer' => m::mock('Illuminate\Contracts\Mail\Mailer'),
            'view'   => 'resetLinkView',
        ];

        return $mocks;
    }
}
