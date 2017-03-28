<?php

use Mockery as m;
use Illuminate\Contracts\Auth\VerifyEmailBroker;

class AuthVerifyEmailBrokerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testBrokerCreatesTokenAndRedirectsWithoutError()
    {
        $mocks = $this->getMocks();
        $broker = $this->getMock('Illuminate\Auth\VerifyEmails\VerifyEmailBroker', ['emailVerificationLink', 'getUri'], array_values($mocks));
        $user = m::mock('Illuminate\Contracts\Auth\CanVerifyEmail');
        $mocks['tokens']->shouldReceive('create')->once()->with($user)->andReturn('token');
        $callback = function () {};
        $broker->expects($this->once())->method('emailVerificationLink')->with($this->equalTo($user), $this->equalTo('token'), $this->equalTo($callback));

        $this->assertEquals(VerifyEmailBroker::VERIFY_LINK_SENT, $broker->sendVerificationLink($user, $callback));
    }

    public function testMailerIsCalledWithProperViewTokenAndCallback()
    {
        unset($_SERVER['__email.verify.test']);
        $broker = $this->getBroker($mocks = $this->getMocks());
        $callback = function ($message, $user) { $_SERVER['__email.verify.test'] = true; };
        $user = m::mock('Illuminate\Contracts\Auth\CanVerifyEmail');
        $mocks['mailer']->shouldReceive('send')->once()->with('verifyLinkView', ['token' => 'token', 'user' => $user], m::type('Closure'))->andReturnUsing(function ($view, $data, $callback) {
            return $callback;
        });
        $user->shouldReceive('getEmailToVerify')->once()->andReturn('email');
        $message = m::mock('StdClass');
        $message->shouldReceive('to')->once()->with('email');
        $result = $broker->emailVerificationLink($user, 'token', $callback);
        call_user_func($result, $message);

        $this->assertTrue($_SERVER['__email.verify.test']);
    }

    public function testRedirectReturnedByVerifyWhenRecordDoesntExistInTable()
    {
        $creds = ['token' => 'token'];
        $broker = $this->getBroker($mocks = $this->getMocks());
        $user = m::mock('Illuminate\Contracts\Auth\CanVerifyEmail');
        $mocks['tokens']->shouldReceive('exists')->with($user, 'token')->andReturn(false);

        $this->assertEquals(VerifyEmailBroker::INVALID_TOKEN, $broker->verify($user, 'token'));
    }

    public function testVerifyRemovesRecordOnReminderTableAndCallsSetVerify()
    {
        $broker = $this->getMock('Illuminate\Auth\VerifyEmails\VerifyEmailBroker', ['validateVerification', 'getToken'], array_values($mocks = $this->getMocks()));
        $user = m::mock('Illuminate\Contracts\Auth\CanVerifyEmail');
        $user->shouldReceive('setVerified')->once();
        $user->shouldReceive('save')->once();
        $broker->expects($this->once())->method('validateVerification')->will($this->returnValue($user));
        $mocks['tokens']->shouldReceive('delete')->once()->with('token');

        $this->assertEquals(VerifyEmailBroker::EMAIL_VERIFIED, $broker->verify($user, 'token'));
    }

    protected function getBroker($mocks)
    {
        return new \Illuminate\Auth\VerifyEmails\VerifyEmailBroker($mocks['tokens'], $mocks['mailer'], $mocks['view']);
    }

    protected function getMocks()
    {
        $mocks = [
            'tokens' => m::mock('Illuminate\Auth\VerifyEmails\TokenRepositoryInterface'),
            'mailer'    => m::mock('Illuminate\Contracts\Mail\Mailer'),
            'view'      => 'verifyLinkView',
        ];

        return $mocks;
    }
}
