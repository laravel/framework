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
    public function testCreateInsertsNewRecordIntoTable()
    {
        $repo = $this->getRepo();
        $repo->getHasher()->expects('make')->andReturn('hashed-token');
        $query = m::mock(stdClass::class);
        $repo->getConnection()->expects('table')->times(2)->with('table')->andReturn($query);
        $query->expects('where')->with('email', 'email')->andReturn($query);
        $query->expects('delete');
        $query->expects('insert');
        $user = m::mock(CanResetPassword::class);
        $user->expects('getEmailForPasswordReset')->times(2)->andReturn('email');

        $results = $repo->create($user);

        $this->assertIsString($results);
        $this->assertGreaterThan(1, strlen($results));
    }

    public function testExistReturnsFalseIfNoRowFoundForUser()
    {
        $repo = $this->getRepo();
        $query = m::mock(stdClass::class);
        $repo->getConnection()->expects('table')->with('table')->andReturn($query);
        $query->expects('where')->with('email', 'email')->andReturn($query);
        $query->expects('first')->andReturn(null);
        $user = m::mock(CanResetPassword::class);
        $user->expects('getEmailForPasswordReset')->andReturn('email');

        $this->assertFalse($repo->exists($user, 'token'));
    }

    public function testExistReturnsFalseIfRecordIsExpired()
    {
        $repo = $this->getRepo();
        $query = m::mock(stdClass::class);
        $repo->getConnection()->expects('table')->with('table')->andReturn($query);
        $query->expects('where')->with('email', 'email')->andReturn($query);
        $date = Carbon::now()->subSeconds(300000)->toDateTimeString();
        $query->expects('first')->andReturn((object) ['created_at' => $date, 'token' => 'hashed-token']);
        $user = m::mock(CanResetPassword::class);
        $user->expects('getEmailForPasswordReset')->andReturn('email');

        $this->assertFalse($repo->exists($user, 'token'));
    }

    public function testExistReturnsTrueIfValidRecordExists()
    {
        $repo = $this->getRepo();
        $repo->getHasher()->expects('check')->with('token', 'hashed-token')->andReturn(true);
        $query = m::mock(stdClass::class);
        $repo->getConnection()->expects('table')->with('table')->andReturn($query);
        $query->expects('where')->with('email', 'email')->andReturn($query);
        $date = Carbon::now()->subMinutes(10)->toDateTimeString();
        $query->expects('first')->andReturn((object) ['created_at' => $date, 'token' => 'hashed-token']);
        $user = m::mock(CanResetPassword::class);
        $user->expects('getEmailForPasswordReset')->andReturn('email');

        $this->assertTrue($repo->exists($user, 'token'));
    }

    public function testExistReturnsFalseIfInvalidToken()
    {
        $repo = $this->getRepo();
        $repo->getHasher()->expects('check')->with('wrong-token', 'hashed-token')->andReturn(false);
        $query = m::mock(stdClass::class);
        $repo->getConnection()->expects('table')->with('table')->andReturn($query);
        $query->expects('where')->with('email', 'email')->andReturn($query);
        $date = Carbon::now()->subMinutes(10)->toDateTimeString();
        $query->expects('first')->andReturn((object) ['created_at' => $date, 'token' => 'hashed-token']);
        $user = m::mock(CanResetPassword::class);
        $user->expects('getEmailForPasswordReset')->andReturn('email');

        $this->assertFalse($repo->exists($user, 'wrong-token'));
    }

    public function testRecentlyCreatedReturnsFalseIfNoRowFoundForUser()
    {
        $repo = $this->getRepo();
        $query = m::mock(stdClass::class);
        $repo->getConnection()->expects('table')->with('table')->andReturn($query);
        $query->expects('where')->with('email', 'email')->andReturn($query);
        $query->expects('first')->andReturn(null);
        $user = m::mock(CanResetPassword::class);
        $user->expects('getEmailForPasswordReset')->andReturn('email');

        $this->assertFalse($repo->recentlyCreatedToken($user));
    }

    public function testRecentlyCreatedReturnsTrueIfRecordIsRecentlyCreated()
    {
        Carbon::setTestNow($now = Carbon::now());

        $repo = $this->getRepo();
        $query = m::mock(stdClass::class);
        $repo->getConnection()->expects('table')->with('table')->andReturn($query);
        $query->expects('where')->with('email', 'email')->andReturn($query);
        $date = $now->subSeconds(59)->toDateTimeString();
        $query->expects('first')->andReturn((object) ['created_at' => $date, 'token' => 'hashed-token']);
        $user = m::mock(CanResetPassword::class);
        $user->expects('getEmailForPasswordReset')->andReturn('email');

        $this->assertTrue($repo->recentlyCreatedToken($user));
    }

    public function testRecentlyCreatedReturnsFalseIfValidRecordExists()
    {
        Carbon::setTestNow($now = Carbon::now());

        $repo = $this->getRepo();
        $query = m::mock(stdClass::class);
        $repo->getConnection()->expects('table')->with('table')->andReturn($query);
        $query->expects('where')->with('email', 'email')->andReturn($query);
        $date = $now->subSeconds(61)->toDateTimeString();
        $query->expects('first')->andReturn((object) ['created_at' => $date, 'token' => 'hashed-token']);
        $user = m::mock(CanResetPassword::class);
        $user->expects('getEmailForPasswordReset')->andReturn('email');

        $this->assertFalse($repo->recentlyCreatedToken($user));
    }

    public function testDeleteMethodDeletesByToken()
    {
        $repo = $this->getRepo();
        $query = m::mock(stdClass::class);
        $repo->getConnection()->expects('table')->with('table')->andReturn($query);
        $query->expects('where')->with('email', 'email')->andReturn($query);
        $query->expects('delete');
        $user = m::mock(CanResetPassword::class);
        $user->expects('getEmailForPasswordReset')->andReturn('email');

        $repo->delete($user);
    }

    public function testDeleteExpiredMethodDeletesExpiredTokens()
    {
        $repo = $this->getRepo();
        $query = m::mock(stdClass::class);
        $repo->getConnection()->expects('table')->with('table')->andReturn($query);
        $query->expects('where')->with('created_at', '<', m::any())->andReturn($query);
        $query->expects('delete');

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
