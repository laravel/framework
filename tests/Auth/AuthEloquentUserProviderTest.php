<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Tests\Database\Concerns\RestoresConnectionResolver;
use Mockery;
use PHPUnit\Framework\TestCase;

class AuthEloquentUserProviderTest extends TestCase
{
    use RestoresConnectionResolver;

    public function testRetrieveByIDReturnsUser()
    {
        $user = $this->newProvider()->retrieveById(3);

        $this->assertInstanceOf(EloquentProviderUserStub::class, $user);
        $this->assertSame('taylor', $user->name);
    }

    public function testRetrieveByTokenReturnsUser()
    {
        $user = $this->newProvider()->retrieveByToken(3, 'a');

        $this->assertInstanceOf(EloquentProviderUserStub::class, $user);
        $this->assertSame('taylor', $user->name);
    }

    public function testRetrieveTokenWithBadIdentifierReturnsNull()
    {
        $this->assertNull($this->newProvider()->retrieveByToken(99, 'a'));
    }

    public function testRetrievingWithOnlyPasswordCredentialReturnsNull()
    {
        $user = $this->newProvider()->retrieveByCredentials(['api_password' => 'foo']);

        $this->assertNull($user);
    }

    public function testRetrieveByBadTokenReturnsNull()
    {
        $provider = $this->newProvider();

        $this->assertNull($provider->retrieveByToken(1, 'a'));
        $this->assertNull($provider->retrieveByToken(3, 'b'));
    }

    public function testRetrieveByCredentialsReturnsUser()
    {
        $user = $this->newProvider()->retrieveByCredentials(['username' => 'dayle', 'password' => 'foo', 'group' => ['one', 'two']]);

        $this->assertInstanceOf(EloquentProviderUserStub::class, $user);
        $this->assertSame('taylor', $user->name);
    }

    public function testRetrieveByCredentialsAcceptsCallback()
    {
        $user = $this->newProvider()->retrieveByCredentials([function ($builder) {
            $builder->where('username', 'dayle');
            $builder->whereIn('group', ['one', 'two']);
        }]);

        $this->assertSame('taylor', $user->name);
    }

    public function testRetrieveByCredentialsWithMultiplyPasswordsReturnsNull()
    {
        $user = $this->newProvider()->retrieveByCredentials([
            'password' => 'dayle',
            'password2' => 'night',
        ]);

        $this->assertNull($user);
    }

    public function testCredentialValidation()
    {
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('check')->with('plain', 'hash')->andReturn(true);
        $provider = new EloquentUserProvider($hasher, 'foo');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertTrue($result);
    }

    public function testCredentialValidationFailed()
    {
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('check')->with('plain', 'hash')->andReturn(false);
        $provider = new EloquentUserProvider($hasher, 'foo');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn('hash');
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertFalse($result);
    }

    public function testCredentialValidationFailsGracefullyWithNullPassword()
    {
        $hasher = Mockery::mock(Hasher::class);
        $hasher->shouldReceive('check')->never();
        $provider = new EloquentUserProvider($hasher, 'foo');
        $user = Mockery::mock(Authenticatable::class);
        $user->expects('getAuthPassword')->andReturn(null);
        $result = $provider->validateCredentials($user, ['password' => 'plain']);

        $this->assertFalse($result);
    }

    public function testRehashPasswordIfRequired()
    {
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('needsRehash')->with('hash')->andReturn(true);
        $hasher->expects('make')->with('plain')->andReturn('rehashed');

        $provider = $this->newProvider($hasher);
        $user = EloquentProviderUserStub::find(3);
        $provider->rehashPasswordIfRequired($user, ['password' => 'plain']);

        $this->assertSame('rehashed', $user->fresh()->password);
    }

    public function testDontRehashPasswordIfNotRequired()
    {
        $hasher = Mockery::mock(Hasher::class);
        $hasher->expects('needsRehash')->with('hash')->andReturn(false);
        $hasher->shouldNotReceive('make');

        $provider = $this->newProvider($hasher);
        $user = EloquentProviderUserStub::find(3);
        $provider->rehashPasswordIfRequired($user, ['password' => 'plain']);

        $this->assertSame('hash', $user->fresh()->password);
    }

    public function testModelsCanBeCreated()
    {
        $hasher = new BcryptHasher;
        $provider = new EloquentUserProvider($hasher, EloquentProviderUserStub::class);
        $model = $provider->createModel();

        $this->assertInstanceOf(EloquentProviderUserStub::class, $model);
    }

    public function testRegistersQueryHandler()
    {
        $callback = function ($builder) {
            $builder->whereIn('group', ['one', 'two']);
        };

        $provider = $this->newProvider();
        $provider->withQuery($callback);
        $user = $provider->retrieveByCredentials([function ($builder) {
            $builder->where('username', 'dayle');
        }]);

        $this->assertSame('taylor', $user->name);
        $this->assertSame($callback, $provider->getQueryCallback());
    }

    protected function newProvider(?Hasher $hasher = null)
    {
        $pdo = $this->useInMemoryConnection()->getPdo();
        $pdo->exec('create table "users" ("id" integer primary key, "username" text, "group" text, "name" text, "remember_token" text, "password" text)');
        $pdo->exec("insert into \"users\" values (1, 'dayle', 'three', 'other', null, null)");
        $pdo->exec("insert into \"users\" values (2, 'sam', 'one', 'third', null, null)");
        $pdo->exec("insert into \"users\" values (3, 'dayle', 'two', 'taylor', 'a', 'hash')");

        return new EloquentUserProvider($hasher ?? new BcryptHasher, EloquentProviderUserStub::class);
    }
}

class EloquentProviderUserStub extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $table = 'users';

    public $timestamps = false;
}
