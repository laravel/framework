<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\DatabaseUserProvider;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Connection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Hashing\BcryptHasher;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class AuthDatabaseUserProviderTest extends TestCase
{
    public function testRetrieveByIDReturnsUserWhenUserIsFound()
    {
        $provider = $this->newProvider();
        $user = $provider->retrieveById(3);

        $this->assertInstanceOf(GenericUser::class, $user);
        $this->assertSame(3, $user->getAuthIdentifier());
        $this->assertSame('taylor', $user->name);
    }

    public function testRetrieveByIDReturnsNullWhenUserIsNotFound()
    {
        $provider = $this->newProvider();
        $user = $provider->retrieveById(99);

        $this->assertNull($user);
    }

    public function testRetrieveByTokenReturnsUser()
    {
        $provider = $this->newProvider();
        $user = $provider->retrieveByToken(3, 'a');

        $this->assertInstanceOf(GenericUser::class, $user);
        $this->assertSame(3, $user->getAuthIdentifier());
    }

    public function testRetrieveTokenWithBadIdentifierReturnsNull()
    {
        $provider = $this->newProvider();
        $user = $provider->retrieveByToken(99, 'a');

        $this->assertNull($user);
    }

    public function testRetrieveByBadTokenReturnsNull()
    {
        $provider = $this->newProvider();
        $user = $provider->retrieveByToken(1, 'a');

        $this->assertNull($user);
    }

    public function testRetrieveByCredentialsReturnsUserWhenUserIsFound()
    {
        $provider = $this->newProvider();
        $user = $provider->retrieveByCredentials(['username' => 'dayle', 'password' => 'foo', 'group' => ['one', 'two']]);

        $this->assertInstanceOf(GenericUser::class, $user);
        $this->assertSame(3, $user->getAuthIdentifier());
        $this->assertSame('taylor', $user->name);
    }

    public function testRetrieveByCredentialsAcceptsCallback()
    {
        $provider = $this->newProvider();

        $user = $provider->retrieveByCredentials([function ($builder) {
            $builder->where('username', 'dayle');
            $builder->whereIn('group', ['one', 'two']);
        }]);

        $this->assertInstanceOf(GenericUser::class, $user);
        $this->assertSame(3, $user->getAuthIdentifier());
        $this->assertSame('taylor', $user->name);
    }

    public function testRetrieveByCredentialsReturnsNullWhenUserIsFound()
    {
        $provider = $this->newProvider();
        $user = $provider->retrieveByCredentials(['username' => 'nobody']);

        $this->assertNull($user);
    }

    public function testRetrieveByCredentialsWithMultiplyPasswordsReturnsNull()
    {
        $conn = new Connection(new PDO('sqlite::memory:'));
        $hasher = new BcryptHasher;
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = $provider->retrieveByCredentials([
            'password' => 'dayle',
            'password2' => 'night',
        ]);

        $this->assertNull($user);
    }

    public function testCredentialValidation()
    {
        $conn = new Connection(new PDO('sqlite::memory:'));
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('check')->with('plain', 'hash')->andReturn(true);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertTrue($result);
    }

    public function testCredentialValidationFails()
    {
        $conn = new Connection(new PDO('sqlite::memory:'));
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('check')->with('plain', 'hash')->andReturn(false);
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertFalse($result);
    }

    public function testCredentialValidationFailsGracefullyWithNullPassword()
    {
        $conn = new Connection(new PDO('sqlite::memory:'));
        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldReceive('check')->never();
        $provider = new DatabaseUserProvider($conn, $hasher, 'foo');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn(null);
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertFalse($result);
    }

    public function testRehashPasswordIfRequired()
    {
        $connection = $this->newConnection();
        $connection->table('foo')->where('id', 3)->update(['password' => (new BcryptHasher(['rounds' => 4]))->make('plain')]);

        $hasher = new BcryptHasher(['rounds' => 5]);
        $provider = new DatabaseUserProvider($connection, $hasher, 'foo');
        $provider->rehashPasswordIfRequired($provider->retrieveById(3), ['password' => 'plain']);

        $hash = $connection->table('foo')->where('id', 3)->value('password');
        $this->assertTrue($hasher->check('plain', $hash));
        $this->assertFalse($hasher->needsRehash($hash));
    }

    public function testDontRehashPasswordIfNotRequired()
    {
        $hasher = new BcryptHasher(['rounds' => 5]);
        $connection = $this->newConnection();
        $connection->table('foo')->where('id', 3)->update(['password' => $hash = $hasher->make('plain')]);

        $provider = new DatabaseUserProvider($connection, $hasher, 'foo');
        $provider->rehashPasswordIfRequired($provider->retrieveById(3), ['password' => 'plain']);

        $this->assertSame($hash, $connection->table('foo')->where('id', 3)->value('password'));
    }

    protected function newConnection(): SQLiteConnection
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('create table "foo" ("id" integer primary key, "username" text, "group" text, "name" text, "password" text, "remember_token" text)');
        $pdo->exec("insert into \"foo\" values (1, 'dayle', 'three', 'other', null, null)");
        $pdo->exec("insert into \"foo\" values (2, 'sam', 'one', 'third', null, null)");
        $pdo->exec("insert into \"foo\" values (3, 'dayle', 'one', 'taylor', null, 'a')");

        return new SQLiteConnection($pdo);
    }

    protected function newProvider(): DatabaseUserProvider
    {
        return new DatabaseUserProvider($this->newConnection(), new BcryptHasher, 'foo');
    }
}
