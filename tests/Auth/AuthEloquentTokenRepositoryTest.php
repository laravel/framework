<?php

namespace Illuminate\Tests\Auth;

use stdClass;
use Mockery as m;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\EloquentTokenRepository;

class AuthEloquentTokenRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testCreateInsertsNewRecordIntoTable()
    {
        $query = m::mock(stdClass::class);
        $query->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $query->shouldReceive('create')->once()->andReturn('bar');
        $query->shouldReceive('delete')->once();

        $repo = $this->getRepo();
        $repo->getHasher()->shouldReceive('make')->once()->andReturn('hashed-token');
        $repo->expects($this->any())->method('createModel')->willReturn($query);

        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->times(2)->andReturn('email');

        $results = $repo->create($user);

        $this->assertIsString($results);
        $this->assertGreaterThan(1, strlen($results));
    }

    public function testExistReturnsFalseIfNoRowFoundForUser()
    {
        $query = m::mock(stdClass::class);
        $query->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $query->shouldReceive('first')->once()->andReturn(null);

        $repo = $this->getRepo();
        $repo->expects($this->once())->method('createModel')->willReturn($query);

        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertFalse($repo->exists($user, 'token'));
    }

    public function testExistReturnsFalseIfRecordIsExpired()
    {
        $query = m::mock(stdClass::class);
        $query->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $date = Carbon::now()->subSeconds(300000)->toDateTimeString();
        $query->shouldReceive('first')->once()->andReturn(['created_at' => $date, 'token' => 'hashed-token']);

        $repo = $this->getRepo();
        $repo->expects($this->once())->method('createModel')->willReturn($query);

        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertFalse($repo->exists($user, 'token'));
    }

    public function testExistReturnsTrueIfValidRecordExists()
    {
        $query = m::mock(stdClass::class);
        $query->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $date = Carbon::now()->subMinutes(10)->toDateTimeString();
        $query->shouldReceive('first')->once()->andReturn(['created_at' => $date, 'token' => 'hashed-token']);

        $repo = $this->getRepo();
        $repo->getHasher()->shouldReceive('check')->once()->with('token', 'hashed-token')->andReturn(true);
        $repo->expects($this->once())->method('createModel')->willReturn($query);

        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertTrue($repo->exists($user, 'token'));
    }

    public function testExistReturnsFalseIfInvalidToken()
    {
        $query = m::mock(stdClass::class);
        $query->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $date = Carbon::now()->subMinutes(10)->toDateTimeString();
        $query->shouldReceive('first')->once()->andReturn(['created_at' => $date, 'token' => 'hashed-token']);

        $repo = $this->getRepo();
        $repo->getHasher()->shouldReceive('check')->once()->with('wrong-token', 'hashed-token')->andReturn(false);
        $repo->expects($this->once())->method('createModel')->willReturn($query);

        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertFalse($repo->exists($user, 'wrong-token'));
    }

    public function testRecentlyCreatedReturnsFalseIfNoRowFoundForUser()
    {
        $query = m::mock(stdClass::class);
        $query->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $query->shouldReceive('first')->once()->andReturn(null);

        $repo = $this->getRepo();
        $repo->expects($this->once())->method('createModel')->willReturn($query);

        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertFalse($repo->recentlyCreatedToken($user));
    }

    public function testRecentlyCreatedReturnsTrueIfRecordIsRecentlyCreated()
    {
        $query = m::mock(stdClass::class);
        $query->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $date = Carbon::now()->subSeconds(59)->toDateTimeString();
        $query->shouldReceive('first')->once()->andReturn(['created_at' => $date, 'token' => 'hashed-token']);

        $repo = $this->getRepo();
        $repo->expects($this->once())->method('createModel')->willReturn($query);

        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertTrue($repo->recentlyCreatedToken($user));
    }

    public function testRecentlyCreatedReturnsFalseIfValidRecordExists()
    {
        $query = m::mock(stdClass::class);
        $query->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $date = Carbon::now()->subSeconds(61)->toDateTimeString();
        $query->shouldReceive('first')->once()->andReturn(['created_at' => $date, 'token' => 'hashed-token']);

        $repo = $this->getRepo();
        $repo->expects($this->once())->method('createModel')->willReturn($query);

        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $this->assertFalse($repo->recentlyCreatedToken($user));
    }

    public function testDeleteMethodDeletesByToken()
    {
        $query = m::mock(stdClass::class);
        $query->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('email', 'email')->andReturn($query);
        $query->shouldReceive('delete')->once();

        $repo = $this->getRepo();
        $repo->expects($this->once())->method('createModel')->willReturn($query);

        $user = m::mock(CanResetPassword::class);
        $user->shouldReceive('getEmailForPasswordReset')->once()->andReturn('email');

        $repo->delete($user);
    }

    public function testDeleteExpiredMethodDeletesExpiredTokens()
    {
        $query = m::mock(stdClass::class);
        $query->shouldReceive('newQuery')->once()->andReturn($query);
        $query->shouldReceive('where')->once()->with('created_at', '<', m::any())->andReturn($query);
        $query->shouldReceive('delete')->once();

        $repo = $this->getRepo();
        $repo->expects($this->once())->method('createModel')->willReturn($query);

        $repo->deleteExpired();
    }

    protected function getRepo()
    {
        $hasher = m::mock(Hasher::class);

        // $repo = $this->getMockBuilder(EloquentTokenRepository::class)->setConstructorArgs([$hasher, 'model', 'key'])->getMock();

        // $repo = $this->createMock(EloquentTokenRepository::class);
        // $repo = $this->getMockBuilder(EloquentTokenRepository::class)->onlyMethods(['create', 'createModel', 'getHasher'])->setConstructorArgs([$hasher, 'model', 'key'])->getMock();
        $repo = $this->getMockBuilder(EloquentTokenRepository::class)
            ->onlyMethods(['createModel',])
            ->setConstructorArgs([$hasher, 'model', 'key'])
            ->getMock();

        // $repo->method('getHasher')->willReturn($hasher);

        // $repo->shouldReceive('getHasher')->andReturn($hasher);

        return $repo;

        return new EloquentTokenRepository(
            m::mock(Hasher::class),
            'model',
            'key'
        );
    }
}
