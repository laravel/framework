<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Auth\Passwords\ResetResponse;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\PasswordBroker as PasswordBrokerContract;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Arr;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class AuthPasswordBrokerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testIfUserIsNotFoundErrorRedirectIsReturned()
    {
        $mocks = $this->getMocks();
        $broker = $this->getMockBuilder(PasswordBroker::class)
            ->onlyMethods(['getUser'])
            ->addMethods(['makeErrorRedirect'])
            ->setConstructorArgs(array_values($mocks))
            ->getMock();
        $broker->expects($this->once())->method('getUser')->willReturn(null);

        $response = $broker->sendResetLink(['credentials']);

        $this->assertSame(PasswordBrokerContract::INVALID_USER, $response->value);
        $this->assertSame(ResetResponse::InvalidUser, $response);
    }

    public function testIfTokenIsRecentlyCreated()
    {
        $mocks = $this->getMocks();
        $broker = $this->getMockBuilder(PasswordBroker::class)->addMethods(['emailResetLink', 'getUri'])->setConstructorArgs(array_values($mocks))->getMock();
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['foo'])->andReturn($user = m::mock(CanResetPassword::class));
        $mocks['tokens']->shouldReceive('recentlyCreatedToken')->once()->with($user)->andReturn(true);
        $user->shouldReceive('sendPasswordResetNotification')->with('token');

        $response = $broker->sendResetLink(['foo']);

        $this->assertSame(PasswordBrokerContract::RESET_THROTTLED, $response->value);
        $this->assertSame(ResetResponse::ResetThrottled, $response);
    }

    public function testGetUserThrowsExceptionIfUserDoesntImplementCanResetPassword()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('User must implement CanResetPassword interface.');

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
        $broker = $this->getMockBuilder(PasswordBroker::class)->addMethods(['emailResetLink', 'getUri'])->setConstructorArgs(array_values($mocks))->getMock();
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['foo'])->andReturn($user = m::mock(CanResetPassword::class));
        $mocks['tokens']->shouldReceive('recentlyCreatedToken')->once()->with($user)->andReturn(false);
        $mocks['tokens']->shouldReceive('create')->once()->with($user)->andReturn('token');
        $user->shouldReceive('sendPasswordResetNotification')->with('token');

        $response = $broker->sendResetLink(['foo']);

        $this->assertSame(PasswordBrokerContract::RESET_LINK_SENT, $response->value);
        $this->assertSame(ResetResponse::ResetLinkSent, $response);
    }

    public function testRedirectIsReturnedByResetWhenUserCredentialsInvalid()
    {
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['creds'])->andReturn(null);

        $this->assertSame(ResetResponse::InvalidUser, $broker->reset(['creds'], function () {
            //
        }));
    }

    public function testRedirectReturnedByRemindWhenRecordDoesntExistInTable()
    {
        $creds = ['token' => 'token'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(Arr::except($creds, ['token']))->andReturn($user = m::mock(CanResetPassword::class));
        $mocks['tokens']->shouldReceive('exists')->with($user, 'token')->andReturn(false);

        $response = $broker->reset($creds, function () {
            //
        });

        $this->assertSame(PasswordBrokerContract::INVALID_TOKEN, $response->value);
        $this->assertSame(ResetResponse::InvalidToken, $response);
    }

    public function testResetRemovesRecordOnReminderTableAndCallsCallback()
    {
        unset($_SERVER['__password.reset.test']);
        $broker = $this->getMockBuilder(PasswordBroker::class)
            ->onlyMethods(['validateReset'])
            ->addMethods(['getPassword', 'getToken'])
            ->setConstructorArgs(array_values($mocks = $this->getMocks()))
            ->getMock();
        $broker->expects($this->once())->method('validateReset')->willReturn($user = m::mock(CanResetPassword::class));
        $mocks['tokens']->shouldReceive('delete')->once()->with($user);
        $callback = function ($user, $password) {
            $_SERVER['__password.reset.test'] = compact('user', 'password');

            return 'foo';
        };

        $response = $broker->reset(['password' => 'password', 'token' => 'token'], $callback);

        $this->assertSame(PasswordBrokerContract::PASSWORD_RESET, $response->value);
        $this->assertSame(ResetResponse::PasswordReset, $response);
        $this->assertEquals(['user' => $user, 'password' => 'password'], $_SERVER['__password.reset.test']);
    }

    public function testExecutesCallbackInsteadOfSendingNotification()
    {
        $executed = false;

        $closure = function () use (&$executed) {
            $executed = true;
        };

        $mocks = $this->getMocks();
        $broker = $this->getMockBuilder(PasswordBroker::class)->addMethods(['emailResetLink', 'getUri'])->setConstructorArgs(array_values($mocks))->getMock();
        $mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(['foo'])->andReturn($user = m::mock(CanResetPassword::class));
        $mocks['tokens']->shouldReceive('recentlyCreatedToken')->once()->with($user)->andReturn(false);
        $mocks['tokens']->shouldReceive('create')->once()->with($user)->andReturn('token');
        $user->shouldReceive('sendPasswordResetNotification')->with('token');

        $response = $broker->sendResetLink(['foo'], $closure);

        $this->assertEquals(PasswordBrokerContract::RESET_LINK_SENT, $response->value);
        $this->assertEquals(ResetResponse::ResetLinkSent, $response);

        $this->assertTrue($executed);
    }

    protected function getBroker($mocks)
    {
        return new PasswordBroker($mocks['tokens'], $mocks['users']);
    }

    protected function getMocks()
    {
        return [
            'tokens' => m::mock(TokenRepositoryInterface::class),
            'users' => m::mock(UserProvider::class),
        ];
    }
}
