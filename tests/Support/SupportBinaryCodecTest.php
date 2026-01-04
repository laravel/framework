<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\BinaryCodec;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Uid\Ulid;

class SupportBinaryCodecTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(BinaryCodec::class);
        $property = $reflection->getProperty('customCodecs');
        $property->setValue(null, []);

        parent::tearDown();
    }

    public function testAllReturnsDefaultFormats()
    {
        $formats = BinaryCodec::all();

        $this->assertArrayHasKey('uuid', $formats);
        $this->assertArrayHasKey('ulid', $formats);
    }

    public function testRegisterAddsCustomFormat()
    {
        BinaryCodec::register('hex', fn ($v) => bin2hex($v ?? ''), fn ($v) => hex2bin($v ?? ''));

        $this->assertArrayHasKey('hex', BinaryCodec::all());
    }

    public function testRegisterOverridesDefaultFormat()
    {
        BinaryCodec::register('uuid', fn ($v) => 'custom-encode', fn ($v) => 'custom-decode');

        $this->assertSame('custom-encode', BinaryCodec::encode('test', 'uuid'));
        $this->assertSame('custom-decode', BinaryCodec::decode('test', 'uuid'));
    }

    #[DataProvider('nullAndEmptyProvider')]
    public function testEncodeReturnsNullForNullAndEmpty($value)
    {
        $this->assertNull(BinaryCodec::encode($value, 'uuid'));
        $this->assertNull(BinaryCodec::encode($value, 'ulid'));
    }

    #[DataProvider('nullAndEmptyProvider')]
    public function testDecodeReturnsNullForNullAndEmpty($value)
    {
        $this->assertNull(BinaryCodec::decode($value, 'uuid'));
        $this->assertNull(BinaryCodec::decode($value, 'ulid'));
    }

    public static function nullAndEmptyProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
        ];
    }

    public function testEncodeThrowsOnInvalidFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format [invalid] is invalid.');

        BinaryCodec::encode('value', 'invalid');
    }

    public function testDecodeThrowsOnInvalidFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format [invalid] is invalid.');

        BinaryCodec::decode('value', 'invalid');
    }

    public function testUuidEncodeFromString()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->assertSame(Uuid::fromString($uuid)->getBytes(), BinaryCodec::encode($uuid, 'uuid'));
    }

    public function testUuidEncodeFromBinary()
    {
        $bytes = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000')->getBytes();

        $this->assertSame($bytes, BinaryCodec::encode($bytes, 'uuid'));
    }

    public function testUuidDecodeFromBinary()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $bytes = Uuid::fromString($uuid)->getBytes();

        $this->assertSame($uuid, BinaryCodec::decode($bytes, 'uuid'));
    }

    public function testUuidDecodeFromString()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->assertSame($uuid, BinaryCodec::decode($uuid, 'uuid'));
    }

    public function testUlidEncodeFromString()
    {
        $ulid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

        $this->assertSame(Ulid::fromString($ulid)->toBinary(), BinaryCodec::encode($ulid, 'ulid'));
    }

    public function testUlidEncodeFromBinary()
    {
        $bytes = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV')->toBinary();

        $this->assertSame($bytes, BinaryCodec::encode($bytes, 'ulid'));
    }

    public function testUlidDecodeFromBinary()
    {
        $ulid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $bytes = Ulid::fromString($ulid)->toBinary();

        $this->assertSame($ulid, BinaryCodec::decode($bytes, 'ulid'));
    }

    public function testUlidDecodeFromString()
    {
        $ulid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

        $this->assertSame($ulid, BinaryCodec::decode($ulid, 'ulid'));
    }
}
