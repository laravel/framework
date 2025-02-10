<?php

namespace Illuminate\Tests\Hashing;

use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Hashing\Argon2IdHasher;
use Illuminate\Hashing\ArgonHasher;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Hashing\HashManager;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HasherTest extends TestCase
{
    public $hashManager;

    protected function setUp(): void
    {
        parent::setUp();

        $container = Container::setInstance(new Container);
        $container->singleton('config', fn () => new Config());

        $this->hashManager = new HashManager($container);
    }

    public function testEmptyHashedValueReturnsFalse()
    {
        $hasher = new BcryptHasher();
        $this->assertFalse($hasher->check('password', ''));
        $hasher = new ArgonHasher();
        $this->assertFalse($hasher->check('password', ''));
        $hasher = new Argon2IdHasher();
        $this->assertFalse($hasher->check('password', ''));
    }

    public function testNullHashedValueReturnsFalse()
    {
        $hasher = new BcryptHasher();
        $this->assertFalse($hasher->check('password', null));
        $hasher = new ArgonHasher();
        $this->assertFalse($hasher->check('password', null));
        $hasher = new Argon2IdHasher();
        $this->assertFalse($hasher->check('password', null));
    }

    public function testBasicBcryptHashing()
    {
        $hasher = new BcryptHasher;
        $value = $hasher->make('password');
        $this->assertNotSame('password', $value);
        $this->assertTrue($hasher->check('password', $value));
        $this->assertFalse($hasher->needsRehash($value));
        $this->assertTrue($hasher->needsRehash($value, ['rounds' => 1]));
        $this->assertSame('bcrypt', password_get_info($value)['algoName']);
        $this->assertGreaterThanOrEqual(12, password_get_info($value)['options']['cost']);
        $this->assertTrue($this->hashManager->isHashed($value));
    }

    public function testBcryptValueTooLong()
    {
        $this->expectException(\InvalidArgumentException::class);
        $hasher = new BcryptHasher(['limit' => 72]);
        $hasher->make(str_repeat('a', 73));
    }

    public function testBasicArgon2iHashing()
    {
        $hasher = new ArgonHasher;
        $value = $hasher->make('password');
        $this->assertNotSame('password', $value);
        $this->assertTrue($hasher->check('password', $value));
        $this->assertFalse($hasher->needsRehash($value));
        $this->assertTrue($hasher->needsRehash($value, ['threads' => 1]));
        $this->assertSame('argon2i', password_get_info($value)['algoName']);
        $this->assertTrue($this->hashManager->isHashed($value));
    }

    public function testBasicArgon2idHashing()
    {
        $hasher = new Argon2IdHasher;
        $value = $hasher->make('password');
        $this->assertNotSame('password', $value);
        $this->assertTrue($hasher->check('password', $value));
        $this->assertFalse($hasher->needsRehash($value));
        $this->assertTrue($hasher->needsRehash($value, ['threads' => 1]));
        $this->assertSame('argon2id', password_get_info($value)['algoName']);
        $this->assertTrue($this->hashManager->isHashed($value));
    }

    #[Depends('testBasicBcryptHashing')]
    public function testBasicBcryptVerification()
    {
        $this->expectException(RuntimeException::class);

        $argonHasher = new ArgonHasher(['verify' => true]);
        $argonHashed = $argonHasher->make('password');
        (new BcryptHasher(['verify' => true]))->check('password', $argonHashed);
    }

    #[Depends('testBasicArgon2iHashing')]
    public function testBasicArgon2iVerification()
    {
        $this->expectException(RuntimeException::class);

        $bcryptHasher = new BcryptHasher(['verify' => true]);
        $bcryptHashed = $bcryptHasher->make('password');
        (new ArgonHasher(['verify' => true]))->check('password', $bcryptHashed);
    }

    #[Depends('testBasicArgon2idHashing')]
    public function testBasicArgon2idVerification()
    {
        $this->expectException(RuntimeException::class);

        $bcryptHasher = new BcryptHasher(['verify' => true]);
        $bcryptHashed = $bcryptHasher->make('password');
        (new Argon2IdHasher(['verify' => true]))->check('password', $bcryptHashed);
    }

    public function testIsHashedWithNonHashedValue()
    {
        $this->assertFalse($this->hashManager->isHashed('foo'));
    }

    public function testBasicBcryptNotSupported()
    {
        $this->expectException(RuntimeException::class);

        (new BcryptHasher(['rounds' => 0]))->make('password');
    }

    public function testBasicArgon2iNotSupported()
    {
        $this->expectException(RuntimeException::class);

        (new ArgonHasher(['time' => 0]))->make('password');
    }

    public function testBasicArgon2idNotSupported()
    {
        $this->expectException(RuntimeException::class);

        (new Argon2IdHasher(['time' => 0]))->make('password');
    }
}
