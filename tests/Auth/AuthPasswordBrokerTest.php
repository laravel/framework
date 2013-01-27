<?php

use Mockery as m;
use Illuminate\Auth\PasswordBroker;

class AuthPasswordBrokerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testIfUserIsNotFoundErrorRedirectIsReturned()
	{
		$mocks = $this->getMocks();
		$broker = $this->getMock('Illuminate\Auth\PasswordBroker', array('getUser', 'makeErrorRedirect'), array_values($mocks));
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
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(array('foo'))->andReturn($user = m::mock('Illuminate\Auth\RemindableInterface'));

		$this->assertEquals($user, $broker->getUser(array('foo')));
	}


	public function testBrokerCreatesReminderAndRedirectsWithoutError()
	{
		unset($_SERVER['__reminder.test']);
		$mocks = $this->getMocks();
		$broker = $this->getMock('Illuminate\Auth\PasswordBroker', array('sendReminder', 'getUri'), array_values($mocks));
		$mocks['users']->shouldReceive('retrieveByCredentials')->once()->with(array('foo'))->andReturn($user = m::mock('Illuminate\Auth\RemindableInterface'));		
		$mocks['reminders']->shouldReceive('create')->once()->with($user)->andReturn('token');
		$callback = function() {};
		$broker->expects($this->once())->method('sendReminder')->with($this->equalTo($user), $this->equalTo('token'), $this->equalTo($callback));
		$mocks['redirect']->shouldReceive('refresh')->once();

		$broker->remind(array('foo'), $callback);
	}


	protected function getBroker($mocks)
	{
		return new PasswordBroker($mocks['reminders'], $mocks['users'], $mocks['redirect'], $mocks['mailer'], $mocks['view']);		
	}


	protected function getMocks()
	{
		$mocks = array(
			'reminders' => m::mock('Illuminate\Auth\ReminderRepositoryInterface'),
			'users'     => m::mock('Illuminate\Auth\UserProviderInterface'),
			'redirect'  => m::mock('Illuminate\Routing\Redirector'),
			'mailer'    => m::mock('Illuminate\Mail\Mailer'),
			'view'      => 'reminderView',
		);

		return $mocks;
	}

}