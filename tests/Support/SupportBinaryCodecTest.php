<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\BinaryCodec;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class SupportBinaryCodecTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset custom formats between tests
        $reflection = new \ReflectionClass(BinaryCodec::class);
        $property = $reflection->getProperty('customCodecs');
        $property->setValue(null, []);

        parent::tearDown();
    }

    public function testFormatsReturnsDefaultFormats()
    {
        $formats = BinaryCodec::all();

        $this->assertArrayHasKey('uuid', $formats);
        $this->assertArrayHasKey('encode', $formats['uuid']);
        $this->assertArrayHasKey('decode', $formats['uuid']);
    }

    public function testRegisterCustomFormat()
    {
        BinaryCodec::register('hex', fn ($v) => bin2hex($v ?? ''), fn ($v) => hex2bin($v ?? ''));
        $formats = BinaryCodec::all();

        $this->assertArrayHasKey('hex', $formats);
    }

    public function testEncodeUuid()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $encoded = BinaryCodec::encode($uuid, 'uuid');

        $this->assertSame(Uuid::fromString($uuid)->getBytes(), $encoded);
    }

    public function testDecodeUuid()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $bytes = Uuid::fromString($uuid)->getBytes();

        $this->assertSame($uuid, BinaryCodec::decode($bytes, 'uuid'));
    }

    public function testEncodeNullReturnsNull()
    {
        $this->assertNull(BinaryCodec::encode(null, 'uuid'));
    }

    public function testDecodeNullReturnsNull()
    {
        $this->assertNull(BinaryCodec::decode(null, 'uuid'));
    }

    public function testEncodeEmptyStringReturnsNull()
    {
        $this->assertNull(BinaryCodec::encode('', 'uuid'));
    }

    public function testDecodeEmptyStringReturnsNull()
    {
        $this->assertNull(BinaryCodec::decode('', 'uuid'));
    }

    public function testEncodeWithInvalidFormatThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format [invalid] is invalid.');

        BinaryCodec::encode('value', 'invalid');
    }

    public function testDecodeWithInvalidFormatThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format [invalid] is invalid.');

        BinaryCodec::decode('value', 'invalid');
    }

    public function testEncodeUuidFromBinaryInput()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $bytes = Uuid::fromString($uuid)->getBytes();

        // Encoding binary should return the same bytes
        $this->assertSame($bytes, BinaryCodec::encode($bytes, 'uuid'));
    }

    public function testDecodeUuidFromStringInput()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        // Decoding string UUID should return the same string
        $this->assertSame($uuid, BinaryCodec::decode($uuid, 'uuid'));
    }

    public function testCustomFormatOverridesDefault()
    {
        BinaryCodec::register('uuid', fn ($v) => 'custom-encode', fn ($v) => 'custom-decode');

        $this->assertSame('custom-encode', BinaryCodec::encode('test', 'uuid'));
        $this->assertSame('custom-decode', BinaryCodec::decode('test', 'uuid'));
    }
}
