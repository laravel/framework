<?php

use Mockery as m;

class AuthDatabaseReminderRepositoryTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testCreateInsertsNewRecordIntoTable()
	{
		$repo = $this->getRepo();
		$repo->getConnection()->shouldReceive('table')->with('table')->andReturn($query = m::mock('StdClass'));
		$query->shouldReceive('where')->with('email', 'email')->andReturn($query);
		$query->shouldReceive('delete')->once();
		$query->shouldReceive('insert')->once();
		$user = m::mock('Illuminate\Auth\Reminders\RemindableInterface');
		$user->shouldReceive('getReminderEmail')->andReturn('email');

		$results = $repo->create($user);

		$this->assertInternalType('string', $results);
		$this->assertGreaterThan(1, strlen($results));
	}


	public function testExistReturnsFalseIfNoRowFoundForUser()
	{
		$repo = $this->getRepo();
		$repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock('StdClass'));
		$query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
		$query->shouldReceive('where')->once()->with('token', 'token')->andReturn($query);
		$query->shouldReceive('first')->andReturn(null);
		$user = m::mock('Illuminate\Auth\Reminders\RemindableInterface');
		$user->shouldReceive('getReminderEmail')->andReturn('email');

		$this->assertFalse($repo->exists($user, 'token'));
	}


	public function testExistReturnsFalseIfRecordIsExpired()
	{
		$repo = $this->getRepo();
		$repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock('StdClass'));
		$query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
		$query->shouldReceive('where')->once()->with('token', 'token')->andReturn($query);
		$date = date('Y-m-d H:i:s', time() - 300000);
		$query->shouldReceive('first')->andReturn((object) array('created_at' => $date));
		$user = m::mock('Illuminate\Auth\Reminders\RemindableInterface');
		$user->shouldReceive('getReminderEmail')->andReturn('email');

		$this->assertFalse($repo->exists($user, 'token'));
	}


	public function testExistReturnsTrueIfValidRecordExists()
	{
		$repo = $this->getRepo();
		$repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock('StdClass'));
		$query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
		$query->shouldReceive('where')->once()->with('token', 'token')->andReturn($query);
		$date = date('Y-m-d H:i:s', time() - 600);
		$query->shouldReceive('first')->andReturn((object) array('created_at' => $date));
		$user = m::mock('Illuminate\Auth\Reminders\RemindableInterface');
		$user->shouldReceive('getReminderEmail')->andReturn('email');

		$this->assertTrue($repo->exists($user, 'token'));
	}


	public function testDeleteMethodDeletesByToken()
	{
		$repo = $this->getRepo();
		$repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock('StdClass'));
		$query->shouldReceive('where')->once()->with('token', 'token')->andReturn($query);
		$query->shouldReceive('delete')->once();

		$repo->delete('token');
	}


	public function testDeleteExpiredMethodDeletesExpiredTokens()
	{
		$repo = $this->getRepo();
		$repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock('StdClass'));
		$query->shouldReceive('where')->once()->with('created_at', '<', m::any())->andReturn($query);
		$query->shouldReceive('delete')->once();

		$repo->deleteExpired();
	}


	protected function getRepo()
	{
		return new Illuminate\Auth\Reminders\DatabaseReminderRepository(m::mock('Illuminate\Database\Connection'), 'table', 'key');
	}

}
