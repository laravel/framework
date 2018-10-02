<?php

namespace Illuminate\Tests\Auth;

use stdClass;
use Mockery as m;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;

class AuthDatabaseTokenRepositoryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());
    }

    public function tearDown()
    {
        parent::tearDown();

        m::close();
        Carbon::setTestNow(null);
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

        $this->assertInternalType('string', $results);
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
