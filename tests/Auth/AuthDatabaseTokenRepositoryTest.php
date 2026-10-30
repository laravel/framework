<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Support\Carbon;
use PDO;
use PHPUnit\Framework\TestCase;

class AuthDatabaseTokenRepositoryTest extends TestCase
{
    public function testCreateInsertsNewRecordIntoTable()
    {
        $repo = $this->getRepo();
        $user = $this->getUser('email');

        $token = $repo->create($user);

        $this->assertIsString($token);
        $this->assertGreaterThan(1, strlen($token));
        $this->assertTrue($repo->exists($user, $token));
    }

    public function testExistReturnsFalseIfNoRowFoundForUser()
    {
        $repo = $this->getRepo();

        $this->assertFalse($repo->exists($this->getUser('email'), 'token'));
    }

    public function testExistReturnsFalseIfRecordIsExpired()
    {
        $repo = $this->getRepo();
        $user = $this->getUser('email');
        $this->insertToken($repo, 'email', 'token', Carbon::now()->subSeconds(300000));

        $this->assertFalse($repo->exists($user, 'token'));
    }

    public function testExistReturnsTrueIfValidRecordExists()
    {
        $repo = $this->getRepo();
        $user = $this->getUser('email');
        $this->insertToken($repo, 'email', 'token', Carbon::now()->subMinutes(10));

        $this->assertTrue($repo->exists($user, 'token'));
    }

    public function testExistReturnsFalseIfInvalidToken()
    {
        $repo = $this->getRepo();
        $user = $this->getUser('email');
        $this->insertToken($repo, 'email', 'token', Carbon::now()->subMinutes(10));

        $this->assertFalse($repo->exists($user, 'wrong-token'));
    }

    public function testRecentlyCreatedReturnsFalseIfNoRowFoundForUser()
    {
        $repo = $this->getRepo();

        $this->assertFalse($repo->recentlyCreatedToken($this->getUser('email')));
    }

    public function testRecentlyCreatedReturnsTrueIfRecordIsRecentlyCreated()
    {
        Carbon::setTestNow($now = Carbon::now());

        $repo = $this->getRepo();
        $user = $this->getUser('email');
        $this->insertToken($repo, 'email', 'token', $now->clone()->subSeconds(59));

        $this->assertTrue($repo->recentlyCreatedToken($user));
    }

    public function testRecentlyCreatedReturnsFalseIfValidRecordExists()
    {
        Carbon::setTestNow($now = Carbon::now());

        $repo = $this->getRepo();
        $user = $this->getUser('email');
        $this->insertToken($repo, 'email', 'token', $now->clone()->subSeconds(61));

        $this->assertFalse($repo->recentlyCreatedToken($user));
    }

    public function testDeleteMethodDeletesByToken()
    {
        $repo = $this->getRepo();
        $user = $this->getUser('email');
        $this->insertToken($repo, 'email', 'token', Carbon::now());

        $repo->delete($user);

        $this->assertFalse($repo->exists($user, 'token'));
    }

    public function testDeleteExpiredMethodDeletesExpiredTokens()
    {
        $repo = $this->getRepo();
        $this->insertToken($repo, 'expired@example.com', 'expired-token', Carbon::now()->subSeconds(3700));
        $this->insertToken($repo, 'recent@example.com', 'recent-token', Carbon::now()->subSeconds(10));

        $repo->deleteExpired();

        $rows = $repo->getConnection()->table('table')->pluck('email')->all();
        $this->assertSame(['recent@example.com'], $rows);
    }

    protected function getRepo()
    {
        $connection = new SQLiteConnection(new PDO('sqlite::memory:'));

        $connection->getSchemaBuilder()->create('table', function ($table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        return new DatabaseTokenRepository($connection, new BcryptHasher, 'table', 'key');
    }

    protected function insertToken(DatabaseTokenRepository $repo, $email, $token, Carbon $createdAt)
    {
        $repo->getConnection()->table('table')->insert([
            'email' => $email,
            'token' => $repo->getHasher()->make($token),
            'created_at' => $createdAt,
        ]);
    }

    protected function getUser($email)
    {
        return new class($email) implements CanResetPassword
        {
            public function __construct(protected $email)
            {
            }

            public function getEmailForPasswordReset()
            {
                return $this->email;
            }

            public function sendPasswordResetNotification($token)
            {
                //
            }
        };
    }
}
