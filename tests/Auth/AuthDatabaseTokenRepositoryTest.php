<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class AuthDatabaseTokenRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testCreateInsertsNewRecordIntoTable()
    {
        $repo = $this->getRepo();
        $repo->getHasher()->shouldReceive('make')->once()->andReturn('hashed-token');
        $repo->getConnection()->shouldReceive('table')->times(2)->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $query->shouldReceive('delete')->once();
        $query->shouldReceive('insert')->once();
        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->times(2)->andReturn('email');

        $results = $repo->create($user);

        $this->assertIsString($results);
        $this->assertGreaterThan(1, strlen($results));
    }

    public function testExistReturnsFalseIfNoRowFoundForUser()
    {
        $repo = $this->getRepo();
        $repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $query->shouldReceive('first')->once()->andReturn(null);
        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertFalse($repo->exists($user, 'token'));
    }

    public function testExistReturnsFalseIfRecordIsExpired()
    {
        $repo = $this->getRepo();
        $repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $date = Carbon::now()->subSeconds(300000)->toDateTimeString();
        $query->shouldReceive('first')->once()->andReturn((object) ['created_at' => $date, 'token' => 'hashed-token']);
        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertFalse($repo->exists($user, 'token'));
    }

    public function testExistReturnsTrueIfValidRecordExists()
    {
        $repo = $this->getRepo();
        $repo->getHasher()->shouldReceive('check')->once()->with('token', 'hashed-token')->andReturn(true);
        $repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $date = Carbon::now()->subMinutes(10)->toDateTimeString();
        $query->shouldReceive('first')->once()->andReturn((object) ['created_at' => $date, 'token' => 'hashed-token']);
        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertTrue($repo->exists($user, 'token'));
    }

    public function testExistReturnsFalseIfInvalidToken()
    {
        $repo = $this->getRepo();
        $repo->getHasher()->shouldReceive('check')->once()->with('wrong-token', 'hashed-token')->andReturn(false);
        $repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $date = Carbon::now()->subMinutes(10)->toDateTimeString();
        $query->shouldReceive('first')->once()->andReturn((object) ['created_at' => $date, 'token' => 'hashed-token']);
        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertFalse($repo->exists($user, 'wrong-token'));
    }

    public function testRecentlyCreatedReturnsFalseIfNoRowFoundForUser()
    {
        $repo = $this->getRepo();
        $repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $query->shouldReceive('first')->once()->andReturn(null);
        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertFalse($repo->recentlyCreatedToken($user));
    }

    public function testRecentlyCreatedReturnsTrueIfRecordIsRecentlyCreated()
    {
        $repo = $this->getRepo();
        $repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $date = Carbon::now()->subSeconds(59)->toDateTimeString();
        $query->shouldReceive('first')->once()->andReturn((object) ['created_at' => $date, 'token' => 'hashed-token']);
        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertTrue($repo->recentlyCreatedToken($user));
    }

    public function testRecentlyCreatedReturnsFalseIfValidRecordExists()
    {
        $repo = $this->getRepo();
        $repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $date = Carbon::now()->subSeconds(61)->toDateTimeString();
        $query->shouldReceive('first')->once()->andReturn((object) ['created_at' => $date, 'token' => 'hashed-token']);
        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertFalse($repo->recentlyCreatedToken($user));
    }

    public function testDeleteMethodDeletesByToken()
    {
        $repo = $this->getRepo();
        $repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $query->shouldReceive('delete')->once();
        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $repo->delete($user);
    }

    public function testDeleteExpiredMethodDeletesExpiredTokens()
    {
        $repo = $this->getRepo();
        $repo->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($query = m::mock(stdClass::class));
        $query->shouldReceive('where')->once()->with('created_at', '<', m::any())->andReturn($query);
        $query->shouldReceive('delete')->once();

        $repo->deleteExpired();
    }

    protected function getRepo()
    {
        return new DatabaseTokenRepository(
            m::mock(Connection::class),
            m::mock(Hasher::class),
            'table', 'key');
    }
}
