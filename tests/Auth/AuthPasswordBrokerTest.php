<?php

use Mockery as m;
use Illuminate\Auth\Reminders\PasswordBroker;

class AuthPasswordBrokerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testIfUserIsNotFoundErrorRedirectIsReturned()
	{
		$mocks = $this->getMocks();
		$broker = $this->getMock('Illuminate\Auth\Reminders\PasswordBroker', array('getUser', 'makeErrorRedirect'), array_values($mocks));
		$broker->expects($this->once())->method('getUser')->will($this->returnValue(null));
		$broker->expects($this->once())->method('makeErrorRedirect')->will($this->returnValue('foo'));

		$this->assertEquals('foo', $broker->remind(array('credentials')));
	}


	/**
	 * @expectedException UnexpectedValueException
	 */
	public function testGetUserThrowsExceptionIfUserDoesntImplementRemindable()
	{
		$broker = $this->getBroker($mocks = $this->getMocks());
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(array('foo'))->andReturn('bar');

		$broker->getUser(array('foo'));
	}


	public function testUserIsRetrievedByCredentials()
	{
		$broker = $this->getBroker($mocks = $this->getMocks());
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(array('foo'))->andReturn($user = m::mock('Illuminate\Auth\Reminders\RemindableInterface'));

		$this->assertEquals($user, $broker->getUser(array('foo')));
	}


	public function testBrokerCreatesReminderAndRedirectsWithoutError()
	{
		unset($_SERVER['__reminder.test']);
		$mocks = $this->getMocks();
		$broker = $this->getMock('Illuminate\Auth\Reminders\PasswordBroker', array('sendReminder', 'getUri'), array_values($mocks));
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(array('foo'))->andReturn($user = m::mock('Illuminate\Auth\Reminders\RemindableInterface'));		
		$mocks['reminders']->shouldReceive('create')->once()->with($user)->andReturn('token');
		$callback = function() {};
		$broker->expects($this->once())->method('sendReminder')->with($this->equalTo($user), $this->equalTo('token'), $this->equalTo($callback));
		$mocks['redirect']->shouldReceive('refresh')->once();

		$broker->remind(array('foo'), $callback);
	}


	public function testMailerIsCalledWithProperViewTokenAndCallback()
	{
		unset($_SERVER['__auth.reminder']);
		$broker = $this->getBroker($mocks = $this->getMocks());
		$callback = function($message, $user) { $_SERVER['__auth.reminder'] = true; };
		$mocks['mailer']->shouldReceive('send')->once()->with('reminderView', array('token' => 'token'), m::type('Closure'))->andReturnUsing(function($view, $data, $callback)
		{
			return $callback;
		});
		$user = m::mock('Illuminate\Auth\Reminders\RemindableInterface');
		$user->shouldReceive('getReminderEmail')->once()->andReturn('email');
		$message = m::mock('StdClass');
		$message->shouldReceive('to')->once()->with('email');
		$result = $broker->sendReminder($user, 'token', $callback);
		call_user_func($result, $message);

		$this->assertTrue($_SERVER['__auth.reminder']);
	}


	public function testRedirectIsReturnedByResetWhenUserCredentialsInvalid()
	{
		$broker = $this->getBroker($mocks = $this->getMocks());
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(array('creds'))->andReturn(null);
		$mocks['redirect']->shouldReceive('refresh')->andReturn($redirect = m::mock('Illuminate\Http\RedirectResponse'));
		$redirect->shouldReceive('with')->once()->with('error', true)->andReturn($redirect);
		$redirect->shouldReceive('with')->once()->with('reason', 'reminders.user')->andReturn($redirect);

		$this->assertInstanceof('Illuminate\Http\RedirectResponse', $broker->reset(array('creds'), function() {}));
	}


	public function testRedirectReturnedByRemindWhenPasswordsDontMatch()
	{
		$broker = $this->getBroker($mocks = $this->getMocks());
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(array('creds'))->andReturn($user = m::mock('Illuminate\Auth\Reminders\RemindableInterface'));
		$mocks['redirect']->shouldReceive('getUrlGenerator')->andReturn($gen = m::mock('StdClass'));
		$gen->shouldReceive('getRequest')->andReturn($request = m::mock('StdClass'));
		$request->shouldReceive('input')->once()->with('password')->andReturn('foo');
		$request->shouldReceive('input')->once()->with('password_confirmation')->andReturn('bar');
		$mocks['redirect']->shouldReceive('refresh')->andReturn($redirect = m::mock('Illuminate\Http\RedirectResponse'));
		$redirect->shouldReceive('with')->once()->with('error', true)->andReturn($redirect);
		$redirect->shouldReceive('with')->once()->with('reason', 'reminders.password')->andReturn($redirect);

		$this->assertInstanceof('Illuminate\Http\RedirectResponse', $broker->reset(array('creds'), function() {}));
	}


	public function testRedirectReturnedByRemindWhenPasswordNotSet()
	{
		$broker = $this->getBroker($mocks = $this->getMocks());
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(array('creds'))->andReturn($user = m::mock('Illuminate\Auth\Reminders\RemindableInterface'));
		$mocks['redirect']->shouldReceive('getUrlGenerator')->andReturn($gen = m::mock('StdClass'));
		$gen->shouldReceive('getRequest')->andReturn($request = m::mock('StdClass'));
		$request->shouldReceive('input')->once()->with('password')->andReturn(null);
		$request->shouldReceive('input')->once()->with('password_confirmation')->andReturn(null);
		$mocks['redirect']->shouldReceive('refresh')->andReturn($redirect = m::mock('Illuminate\Http\RedirectResponse'));
		$redirect->shouldReceive('with')->once()->with('error', true)->andReturn($redirect);
		$redirect->shouldReceive('with')->once()->with('reason', 'reminders.password')->andReturn($redirect);

		$this->assertInstanceof('Illuminate\Http\RedirectResponse', $broker->reset(array('creds'), function() {}));
	}


	public function testRedirectReturnedByRemindWhenPasswordsLessThanSixCharacters()
	{
		$broker = $this->getBroker($mocks = $this->getMocks());
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(array('creds'))->andReturn($user = m::mock('Illuminate\Auth\Reminders\RemindableInterface'));
		$mocks['redirect']->shouldReceive('getUrlGenerator')->andReturn($gen = m::mock('StdClass'));
		$gen->shouldReceive('getRequest')->andReturn($request = m::mock('StdClass'));
		$request->shouldReceive('input')->once()->with('password')->andReturn('abc');
		$request->shouldReceive('input')->once()->with('password_confirmation')->andReturn('abc');
		$mocks['redirect']->shouldReceive('refresh')->andReturn($redirect = m::mock('Illuminate\Http\RedirectResponse'));
		$redirect->shouldReceive('with')->once()->with('error', true)->andReturn($redirect);
		$redirect->shouldReceive('with')->once()->with('reason', 'reminders.password')->andReturn($redirect);

		$this->assertInstanceof('Illuminate\Http\RedirectResponse', $broker->reset(array('creds'), function() {}));
	}


	public function testRedirectReturnedByRemindWhenRecordDoesntExistInTable()
	{
		$broker = $this->getMock('Illuminate\Auth\Reminders\PasswordBroker', array('validNewPasswords'), array_values($mocks = $this->getMocks()));
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(array('creds'))->andReturn($user = m::mock('Illuminate\Auth\Reminders\RemindableInterface'));
		$broker->expects($this->once())->method('validNewPasswords')->will($this->returnValue(true));
		$mocks['redirect']->shouldReceive('getUrlGenerator')->andReturn($gen = m::mock('StdClass'));
		$gen->shouldReceive('getRequest')->andReturn($request = m::mock('StdClass'));
		$request->shouldReceive('input')->once()->with('token')->andReturn('token');
		$mocks['reminders']->shouldReceive('exists')->with($user, 'token')->andReturn(false);
		$mocks['redirect']->shouldReceive('refresh')->andReturn($redirect = m::mock('Illuminate\Http\RedirectResponse'));
		$redirect->shouldReceive('with')->once()->with('error', true)->andReturn($redirect);
		$redirect->shouldReceive('with')->once()->with('reason', 'reminders.token')->andReturn($redirect);

		$this->assertInstanceof('Illuminate\Http\RedirectResponse', $broker->reset(array('creds'), function() {}));
	}


	public function testResetRemovesRecordOnReminderTableAndCallsCallback()
	{
		unset($_SERVER['__auth.reminder']);
		$broker = $this->getMock('Illuminate\Auth\Reminders\PasswordBroker', array('validateReset', 'getPassword', 'getToken'), array_values($mocks = $this->getMocks()));
		$broker->expects($this->once())->method('validateReset')->will($this->returnValue($user = m::mock('Illuminate\Auth\Reminders\RemindableInterface')));
		$broker->expects($this->once())->method('getPassword')->will($this->returnValue('password'));
		$broker->expects($this->once())->method('getToken')->will($this->returnValue('token'));
		$mocks['reminders']->shouldReceive('delete')->once()->with('token');
		$callback = function($user, $password)
		{
			$_SERVER['__auth.reminder'] = compact('user', 'password');
			return 'foo';
		};

		$this->assertEquals('foo', $broker->reset(array('creds'), $callback));
		$this->assertEquals(array('user' => $user, 'password' => 'password'), $_SERVER['__auth.reminder']);
	}


	protected function getBroker($mocks)
	{
		return new PasswordBroker($mocks['reminders'], $mocks['users'], $mocks['redirect'], $mocks['mailer'], $mocks['view']);		
	}


	protected function getMocks()
	{
		$mocks = array(
			'reminders' => m::mock('Illuminate\Auth\Reminders\ReminderRepositoryInterface'),
			'users'     => m::mock('Illuminate\Auth\UserProviderInterface'),
			'redirect'  => m::mock('Illuminate\Routing\Redirector'),
			'mailer'    => m::mock('Illuminate\Mail\Mailer'),
			'view'      => 'reminderView',
		);

		return $mocks;
	}

}