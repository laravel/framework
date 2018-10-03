<?php

namespace Illuminate\Tests\Auth;

use Mockery as m;
use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Contracts\Auth\PasswordBroker as PasswordBrokerContract;

class AuthPasswordBrokerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testIfUserIsNotFoundErrorRedirectIsReturned()
    {
        $mocks = $this->getMocks();
        $broker = $this->getMockBuilder(PasswordBroker::class)->setMethods(['getUser', 'makeErrorRedirect'])->setConstructorArgs(array_values($mocks))->getMock();
        $broker->expects($this->once())->method('getUser')->will($this->returnValue(null));

        $this->assertEquals(PasswordBrokerContract::INVALID_USER, $broker->sendResetLink(['credentials']));
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage User must implement CanResetPassword interface.
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
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['foo'])->andReturn($user = m::mock(CanResetPassword::class));

        $this->assertEquals($user, $broker->getUser(['foo']));
    }

    public function testBrokerCreatesTokenAndRedirectsWithoutError()
    {
        $mocks = $this->getMocks();
        $broker = $this->getMockBuilder(PasswordBroker::class)->setMethods(['emailResetLink', 'getUri'])->setConstructorArgs(array_values($mocks))->getMock();
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['foo'])->andReturn($user = m::mock(CanResetPassword::class));
        $mocks['tokens']->shouldReceive('create')->once()->with($user)->andReturn('token');
        $callback = function () {
        };
        $user->shouldReceive('sendPasswordResetNotification')->with('token');

        $this->assertEquals(PasswordBrokerContract::RESET_LINK_SENT, $broker->sendResetLink(['foo'], $callback));
    }

    public function testRedirectIsReturnedByResetWhenUserCredentialsInvalid()
    {
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['creds'])->andReturn(null);

        $this->assertEquals(PasswordBrokerContract::INVALID_USER, $broker->reset(['creds'], function () {
        }));
    }

    public function testRedirectReturnedByRemindWhenPasswordsDontMatch()
    {
        $creds = ['password' => 'foo', 'password_confirmation' => 'bar'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user = m::mock(CanResetPassword::class));

        $this->assertEquals(PasswordBrokerContract::INVALID_PASSWORD, $broker->reset($creds, function () {
        }));
    }

    public function testRedirectReturnedByRemindWhenPasswordNotSet()
    {
        $creds = ['password' => null, 'password_confirmation' => null];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user = m::mock(CanResetPassword::class));

        $this->assertEquals(PasswordBrokerContract::INVALID_PASSWORD, $broker->reset($creds, function () {
        }));
    }

    public function testRedirectReturnedByRemindWhenPasswordsLessThanSixCharacters()
    {
        $creds = ['password' => 'abc', 'password_confirmation' => 'abc'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user = m::mock(CanResetPassword::class));

        $this->assertEquals(PasswordBrokerContract::INVALID_PASSWORD, $broker->reset($creds, function () {
        }));
    }

    public function testRedirectReturnedByRemindWhenPasswordDoesntPassValidator()
    {
        $creds = ['password' => 'abcdef', 'password_confirmation' => 'abcdef'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $broker->validator(function ($credentials) {
            return strlen($credentials['password']) >= 7;
        });
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user = m::mock(CanResetPassword::class));

        $this->assertEquals(PasswordBrokerContract::INVALID_PASSWORD, $broker->reset($creds, function () {
        }));
    }

    public function testRedirectReturnedByRemindWhenRecordDoesntExistInTable()
    {
        $creds = ['token' => 'token'];
        $broker = $this->getMockBuilder(PasswordBroker::class)->setMethods(['validateNewPassword'])->setConstructorArgs(array_values($mocks = $this->getMocks()))->getMock();
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(Arr::except($creds, ['token']))->andReturn($user = m::mock(CanResetPassword::class));
        $broker->expects($this->once())->method('validateNewPassword')->will($this->returnValue(true));
        $mocks['tokens']->shouldReceive('exists')->with($user, 'token')->andReturn(false);

        $this->assertEquals(PasswordBrokerContract::INVALID_TOKEN, $broker->reset($creds, function () {
        }));
    }

    public function testResetRemovesRecordOnReminderTableAndCallsCallback()
    {
        unset($_SERVER['__password.reset.test']);
        $broker = $this->getMockBuilder(PasswordBroker::class)->setMethods(['validateReset', 'getPassword', 'getToken'])->setConstructorArgs(array_values($mocks = $this->getMocks()))->getMock();
        $broker->expects($this->once())->method('validateReset')->will($this->returnValue($user = m::mock(CanResetPassword::class)));
        $mocks['tokens']->shouldReceive('delete')->once()->with($user);
        $callback = function ($user, $password) {
            $_SERVER['__password.reset.test'] = compact('user', 'password');

            return 'foo';
        };

        $this->assertEquals(PasswordBrokerContract::PASSWORD_RESET, $broker->reset(['password' => 'password', 'token' => 'token'], $callback));
        $this->assertEquals(['user' => $user, 'password' => 'password'], $_SERVER['__password.reset.test']);
    }

    protected function getBroker($mocks)
    {
        return new PasswordBroker($mocks['tokens'], $mocks['users'], $mocks['mailer'], $mocks['view']);
    }

    protected function getMocks()
    {
        return [
            'tokens' => m::mock(TokenRepositoryInterface::class),
            'users'  => m::mock(UserProvider::class),
            'mailer' => m::mock(Mailer::class),
            'view'   => 'resetLinkView',
        ];
    }
}
