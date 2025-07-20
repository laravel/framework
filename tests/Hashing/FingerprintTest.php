<?php

namespace Illuminate\Tests\Hashing;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Foundation\Auth\User;
use Illuminate\Hashing\Fingerprinter;
use JsonSerializable;
use Orchestra\Testbench\TestCase;
use Stringable;

class FingerprintTest extends TestCase
{
    protected function fingerprinter(): Fingerprinter
    {
        return $this->app->make('fingerprint');
    }

    public function test_makes_hash_using_xxh3_by_default(): void
    {
        $this->assertSame(hash('xxh3', 'test', true), $this->fingerprinter()->binary('test'));
    }

    public function test_makes_hash_using_config(): void
    {
        $this->app->make('config')->set('hashing.fingerprint', 'sha256');

        $this->assertSame(hash('sha256', 'test', true), $this->fingerprinter()->binary('test'));
    }

    public function test_makes_hash_using_different_algorithm_at_runtime(): void
    {
        $this->assertSame(hash('sha256', 'test', true), $this->fingerprinter()->binary('test', 'sha256'));
    }

    public function test_hashes_with_option(): void
    {
        $hashable = 'test';

        $hash = hash('xxh3', $hashable, true, $options = [
            'seed' => 'test-seed',
        ]);

        $this->assertSame($hash, $this->fingerprinter()->binary($hashable, options: $options));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->make($hashable, options: $options));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->base64($hashable, options: $options));
        $this->assertSame(rtrim(strtr($hash, ['+' => '-', '/' => '_']), '='), $this->fingerprinter()->base64Url($hashable, options: $options));
        $this->assertSame(bin2hex($hash), $this->fingerprinter()->hex($hashable, options: $options));
    }

    public function test_hashes_string(): void
    {
        $hashable = 'test';

        $hash = hash('xxh3', $hashable, true);

        $this->assertSame($hash, $this->fingerprinter()->binary($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->make($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->base64($hashable));
        $this->assertSame(rtrim(strtr($hash, ['+' => '-', '/' => '_']), '='), $this->fingerprinter()->base64Url($hashable));
        $this->assertSame(bin2hex($hash), $this->fingerprinter()->hex($hashable));
    }

    public function test_hashes_stringable(): void
    {
        $hashable = new class implements Stringable
        {
            public function __toString(): string
            {
                return 'test';
            }
        };

        $hash = hash('xxh3', $hashable, true);

        $this->assertSame($hash, $this->fingerprinter()->binary($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->make($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->base64($hashable));
        $this->assertSame(rtrim(strtr($hash, ['+' => '-', '/' => '_']), '='), $this->fingerprinter()->base64Url($hashable));
        $this->assertSame(bin2hex($hash), $this->fingerprinter()->hex($hashable));
    }

    public function test_hashes_resource(): void
    {
        $resource = fopen('php://memory', '+r');

        fwrite($resource, 'test');

        $hash = hash('xxh3', json_encode('test'), true);

        try {
            $this->assertSame($hash, $this->fingerprinter()->binary($resource));
            $this->assertSame(base64_encode($hash), $this->fingerprinter()->make($resource));
            $this->assertSame(base64_encode($hash), $this->fingerprinter()->base64($resource));
            $this->assertSame(rtrim(strtr($hash, ['+' => '-', '/' => '_']), '='), $this->fingerprinter()->base64Url($resource));
            $this->assertSame(bin2hex($hash), $this->fingerprinter()->hex($resource));
        } finally {
            fclose($resource);
        }
    }

    public function test_hashes_iterable(): void
    {
        $hashable = ['test'];

        $hash = hash('xxh3', '"test"', true);

        $this->assertSame($hash, $this->fingerprinter()->binary($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->make($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->base64($hashable));
        $this->assertSame(rtrim(strtr($hash, ['+' => '-', '/' => '_']), '='), $this->fingerprinter()->base64Url($hashable));
        $this->assertSame(bin2hex($hash), $this->fingerprinter()->hex($hashable));
    }

    public function test_hashes_arrayable(): void
    {
        $hashable = new class implements Arrayable
        {
            public function toArray()
            {
                return ['test'];
            }
        };

        $hash = hash('xxh3', '"test"', true);

        $this->assertSame($hash, $this->fingerprinter()->binary($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->make($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->base64($hashable));
        $this->assertSame(rtrim(strtr($hash, ['+' => '-', '/' => '_']), '='), $this->fingerprinter()->base64Url($hashable));
        $this->assertSame(bin2hex($hash), $this->fingerprinter()->hex($hashable));
    }

    public function test_hashes_jsonable(): void
    {
        $hashable = new class implements Jsonable
        {
            public function toJson($options = 0)
            {
                return '"test"';
            }
        };

        $hash = hash('xxh3', '"test"', true);

        $this->assertSame($hash, $this->fingerprinter()->binary($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->make($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->base64($hashable));
        $this->assertSame(rtrim(strtr($hash, ['+' => '-', '/' => '_']), '='), $this->fingerprinter()->base64Url($hashable));
        $this->assertSame(bin2hex($hash), $this->fingerprinter()->hex($hashable));
    }

    public function test_hashes_json_serializable(): void
    {
        $hashable = new class implements JsonSerializable
        {
            public function jsonSerialize(): string
            {
                return 'test';
            }
        };

        $hash = hash('xxh3', '"test"', true);

        $this->assertSame($hash, $this->fingerprinter()->binary($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->make($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->base64($hashable));
        $this->assertSame(rtrim(strtr($hash, ['+' => '-', '/' => '_']), '='), $this->fingerprinter()->base64Url($hashable));
        $this->assertSame(bin2hex($hash), $this->fingerprinter()->hex($hashable));
    }

    public function test_hashes_model(): void
    {
        $hashable = User::make()->forceFill(['name' => 'test']);

        $hash = hash('xxh3', $hashable->toJson(), true);

        $this->assertSame($hash, $this->fingerprinter()->binary($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->make($hashable));
        $this->assertSame(base64_encode($hash), $this->fingerprinter()->base64($hashable));
        $this->assertSame(rtrim(strtr($hash, ['+' => '-', '/' => '_']), '='), $this->fingerprinter()->base64Url($hashable));
        $this->assertSame(bin2hex($hash), $this->fingerprinter()->hex($hashable));
    }

    public function test_compares_hashes(): void
    {
        $expected = hash('xxh3', 'test', true);

        $this->assertTrue($this->fingerprinter()->is($expected, $expected));
        $this->assertFalse($this->fingerprinter()->is($expected, 'different'));

        $this->assertFalse($this->fingerprinter()->isNot($expected, $expected));
        $this->assertTrue($this->fingerprinter()->isNot($expected, 'different'));
    }
}
