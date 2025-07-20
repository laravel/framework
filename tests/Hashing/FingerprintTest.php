<?php

namespace Illuminate\Tests\Hashing;

use ArrayIterator;
use Illuminate\Foundation\Auth\User;
use Illuminate\Hashing\Fingerprint;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class FingerprintTest extends TestCase
{
    protected function setUp(): void
    {
        Fingerprint::$use = 'xxh3';
    }

    public function test_returns_value(): void
    {
        $object = (object)[];

        $fingerprint = Fingerprint::of($object);

        $this->assertSame($object, $fingerprint->value());
    }

    public function test_returns_algorithm(): void
    {
        $fingerprint = Fingerprint::of('test');

        $this->assertSame(Fingerprint::$use, $fingerprint->uses());

        $fingerprint = Fingerprint::of('test', 'algo');

        $this->assertSame('algo', $fingerprint->uses());
    }

    public function test_hashes_string(): void
    {
        $fingerprint = Fingerprint::of('test');

        $this->assertSame('nsn3kY19/EA=', $fingerprint->hash());
    }

    public function test_hashes_stringable(): void
    {
        $fingerprint = Fingerprint::of(Str::of('test'));

        $this->assertSame('nsn3kY19/EA=', $fingerprint->hash());
    }

    public function test_hashes_array(): void
    {
        $fingerprint = Fingerprint::of(str_split('test'));

        $this->assertSame('TEO9OatCBX8=', $fingerprint->hash());
    }

    public function test_hashes_resource(): void
    {
        $resource = fopen(__DIR__ . '/fixtures/fingerprintable.txt', 'r');

        $fingerprint = Fingerprint::of($resource);

        $this->assertSame('6Eq/UI1GMuc=', $fingerprint->hash());
    }

    public function test_hashes_iterable(): void
    {
        $fingerprint = Fingerprint::of(new ArrayIterator(str_split('test')));

        $this->assertSame('TEO9OatCBX8=', $fingerprint->hash());
    }

    public function test_hashes_model(): void
    {
        $fingerprint = Fingerprint::of((new User())->forceFill(['name' => 'test']));

        $this->assertSame('ANVzoYpsoh0=', $fingerprint->hash());
    }

    public function test_hash_is_cached(): void
    {
        $changes = (new User())->forceFill(['name' => 'test']);

        $fingerprint = Fingerprint::of($changes);

        $this->assertSame('ANVzoYpsoh0=', $fingerprint->hash());

        $changes->forceFill(['name' => 'other-test']);

        $this->assertSame('ANVzoYpsoh0=', $fingerprint->hash());
    }

    public function test_raw_hash(): void
    {
        $fingerprint = Fingerprint::of('test');

        $this->assertSame(Str::fromBase64($fingerprint->hash()), $fingerprint->raw());
    }

    public function test_hex_hash(): void
    {
        $fingerprint = Fingerprint::of('test');

        $this->assertSame(bin2hex($fingerprint->raw()), $fingerprint->hex());
    }

    public function test_base64_url_safe_hash(): void
    {
        $fingerprint = new Fingerprint(null, 'test', [], Str::fromBase64('QCN+xb/igqw='));

        $this->assertSame('QCN-xb_igqw', $fingerprint->base64Url());
    }

    public function test_serializes_into_string_as_hash(): void
    {
        $fingerprint = Fingerprint::of('test');

        $this->assertSame('nsn3kY19/EA=', (string) $fingerprint);
    }

    public function test_rehashes_the_same_value(): void
    {
        $changes = (new User())->forceFill(['name' => 'test']);

        $fingerprint = Fingerprint::of($changes);

        $this->assertSame('ANVzoYpsoh0=', $fingerprint->hash());

        $changes->forceFill(['name' => 'other-test']);

        $this->assertSame('4Ginwb2rBAs=', $fingerprint->rehash());
    }

    public function test_uses_non_default_hashing_algorithm(): void
    {
        $fingerprint = Fingerprint::of('test', 'sha256');

        $this->assertSame('n4bQgYhMfWWaL+qgxVrQFaO/TxsrC4Is0V1sFbDwCgg=', $fingerprint->hash());
    }

    public function test_uses_hashing_options(): void
    {
        $fingerprint = Fingerprint::of('test', 'xxh3', [
            'seed' => 'test'
        ]);

        $this->assertSame('nsn3kY19/EA=', $fingerprint->hash());
    }

    public function test_changes_default_algorithm(): void
    {
        Fingerprint::$use = 'sha256';

        $fingerprint = Fingerprint::of('test');

        $this->assertSame('n4bQgYhMfWWaL+qgxVrQFaO/TxsrC4Is0V1sFbDwCgg=', $fingerprint->hash());
    }

    public function test_compares_an_equal_hash(): void
    {
        $fingerprint = Fingerprint::of('test');

        $equal = $fingerprint->hash();

        $this->assertTrue($fingerprint->is($equal));
        $this->assertFalse($fingerprint->isNot($equal));

        $this->assertTrue($fingerprint->is(Str::fromBase64($equal), false));
        $this->assertFalse($fingerprint->isNot(Str::fromBase64($equal), false));
    }

    public function test_compares_a_different_hash(): void
    {
        $fingerprint = Fingerprint::of('test');

        $different = 'different';

        $this->assertFalse($fingerprint->is($different));
        $this->assertTrue($fingerprint->isNot($different));

        $this->assertFalse($fingerprint->is(Str::fromBase64($different), false));
        $this->assertTrue($fingerprint->isNot(Str::fromBase64($different), false));
    }

    public function test_compares_an_equal_fingerprint_instance(): void
    {
        $fingerprint = Fingerprint::of('test');

        $equal = Fingerprint::of('test');

        $this->assertTrue($fingerprint->is($equal));
        $this->assertFalse($fingerprint->isNot($equal));

        $this->assertTrue($fingerprint->is(Str::fromBase64($equal), false));
        $this->assertFalse($fingerprint->isNot(Str::fromBase64($equal), false));
    }

    public function test_compares_a_different_fingerprint_instance(): void
    {
        $fingerprint = Fingerprint::of('test');

        $different = Fingerprint::of('different');

        $this->assertFalse($fingerprint->is($different));
        $this->assertTrue($fingerprint->isNot($different));

        $this->assertFalse($fingerprint->is(Str::fromBase64($different), false));
        $this->assertTrue($fingerprint->isNot(Str::fromBase64($different), false));
    }
}
