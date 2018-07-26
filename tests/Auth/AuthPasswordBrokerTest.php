<?php

use Mockery as m;
use Illuminate\Auth\Reminders\PasswordBroker;

class AuthPasswordBrokerTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testIfUserIsNotFoundErrorRedirectIsReturned()
	{
		$mocks = $this->getMocks();
		$broker = $this->getMock('Illuminate\Auth\Reminders\PasswordBroker', array('getUser', 'makeErrorRedirect'), array_values($mocks));
		$broker->expects($this->once())->method('getUser')->will($this->returnValue(null));

		$this->assertEquals(PasswordBroker::INVALID_USER, $broker->remind(array('credentials')));
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

		$this->assertEquals(PasswordBroker::REMINDER_SENT, $broker->remind(array('foo'), $callback));
	}


	public function testMailerIsCalledWithProperViewTokenAndCallback()
	{
		unset($_SERVER['__auth.reminder']);
		$broker = $this->getBroker($mocks = $this->getMocks());
		$callback = function($message, $user) { $_SERVER['__auth.reminder'] = true; };
		$user = m::mock('Illuminate\Auth\Reminders\RemindableInterface');
		$mocks['mailer']->shouldReceive('send')->once()->with('reminderView', array('token' => 'token', 'user' => $user), m::type('Closure'))->andReturnUsing(function($view, $data, $callback)
		{
			return $callback;
		});
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

		$this->assertEquals(PasswordBroker::INVALID_USER, $broker->reset(array('creds'), function() {}));
	}


	public function testRedirectReturnedByRemindWhenPasswordsDontMatch()
	{
		$creds = array('password' => 'foo', 'password_confirmation' => 'bar');
		$broker = $this->getBroker($mocks = $this->getMocks());
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user = m::mock('Illuminate\Auth\Reminders\RemindableInterface'));

		$this->assertEquals(PasswordBroker::INVALID_PASSWORD, $broker->reset($creds, function() {}));
	}


	public function testRedirectReturnedByRemindWhenPasswordNotSet()
	{
		$creds = array('password' => null, 'password_confirmation' => null);
		$broker = $this->getBroker($mocks = $this->getMocks());
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user = m::mock('Illuminate\Auth\Reminders\RemindableInterface'));

		$this->assertEquals(PasswordBroker::INVALID_PASSWORD, $broker->reset($creds, function() {}));
	}


	public function testRedirectReturnedByRemindWhenPasswordsLessThanSixCharacters()
	{
		$creds = array('password' => 'abc', 'password_confirmation' => 'abc');
		$broker = $this->getBroker($mocks = $this->getMocks());
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user = m::mock('Illuminate\Auth\Reminders\RemindableInterface'));

		$this->assertEquals(PasswordBroker::INVALID_PASSWORD, $broker->reset($creds, function() {}));
	}


	public function testRedirectReturnedByRemindWhenPasswordDoesntPassValidator()
	{
		$creds = array('password' => 'abcdef', 'password_confirmation' => 'abcdef');
		$broker = $this->getBroker($mocks = $this->getMocks());
		$broker->validator(function($credentials) { return strlen($credentials['password']) >= 7; });
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with($creds)->andReturn($user = m::mock('Illuminate\Auth\Reminders\RemindableInterface'));

		$this->assertEquals(PasswordBroker::INVALID_PASSWORD, $broker->reset($creds, function() {}));
	}


	public function testRedirectReturnedByRemindWhenRecordDoesntExistInTable()
	{
		$creds = array('token' => 'token');
		$broker = $this->getMock('Illuminate\Auth\Reminders\PasswordBroker', array('validNewPasswords'), array_values($mocks = $this->getMocks()));
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(array_except($creds, array('token')))->andReturn($user = m::mock('Illuminate\Auth\Reminders\RemindableInterface'));
		$broker->expects($this->once())->method('validNewPasswords')->will($this->returnValue(true));
		$mocks['reminders']->shouldReceive('exists')->with($user, 'token')->andReturn(false);

		$this->assertEquals(PasswordBroker::INVALID_TOKEN, $broker->reset($creds, function() {}));
	}


	public function testResetRemovesRecordOnReminderTableAndCallsCallback()
	{
		unset($_SERVER['__auth.reminder']);
		$broker = $this->getMock('Illuminate\Auth\Reminders\PasswordBroker', array('validateReset', 'getPassword', 'getToken'), array_values($mocks = $this->getMocks()));
		$broker->expects($this->once())->method('validateReset')->will($this->returnValue($user = m::mock('Illuminate\Auth\Reminders\RemindableInterface')));
		$mocks['reminders']->shouldReceive('delete')->once()->with('token');
		$callback = function($user, $password)
		{
			$_SERVER['__auth.reminder'] = compact('user', 'password');
			return 'foo';
		};

		$this->assertEquals(PasswordBroker::PASSWORD_RESET, $broker->reset(array('password' => 'password', 'token' => 'token'), $callback));
		$this->assertEquals(array('user' => $user, 'password' => 'password'), $_SERVER['__auth.reminder']);
	}


	protected function getBroker($mocks)
	{
		return new PasswordBroker($mocks['reminders'], $mocks['users'], $mocks['mailer'], $mocks['view']);
	}


	protected function getMocks()
	{
		$mocks = array(
			'reminders' => m::mock('Illuminate\Auth\Reminders\ReminderRepositoryInterface'),
			'users'     => m::mock('Illuminate\Auth\UserProviderInterface'),
			'mailer'    => m::mock('Illuminate\Mail\Mailer'),
			'view'      => 'reminderView',
		);

		return $mocks;
	}

}
