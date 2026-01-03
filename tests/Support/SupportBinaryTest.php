<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Binary;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class SupportBinaryTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset custom formats between tests
        $reflection = new \ReflectionClass(Binary::class);
        $property = $reflection->getProperty('customFormats');
        $property->setAccessible(true);
        $property->setValue(null, []);

        parent::tearDown();
    }

    public function testFormatsReturnsDefaultFormats()
    {
        $formats = Binary::formats();

        $this->assertArrayHasKey('uuid', $formats);
        $this->assertArrayHasKey('encode', $formats['uuid']);
        $this->assertArrayHasKey('decode', $formats['uuid']);
    }

    public function testRegisterCustomFormat()
    {
        Binary::registerFormat('hex', fn ($v) => bin2hex($v ?? ''), fn ($v) => hex2bin($v ?? ''));

        $formats = Binary::formats();

        $this->assertArrayHasKey('hex', $formats);
    }

    public function testEncodeUuid()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $encoded = Binary::encode($uuid, 'uuid');

        $this->assertSame(Uuid::fromString($uuid)->getBytes(), $encoded);
    }

    public function testDecodeUuid()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $bytes = Uuid::fromString($uuid)->getBytes();

        $this->assertSame($uuid, Binary::decode($bytes, 'uuid'));
    }

    public function testEncodeNullReturnsNull()
    {
        $this->assertNull(Binary::encode(null, 'uuid'));
    }

    public function testDecodeNullReturnsNull()
    {
        $this->assertNull(Binary::decode(null, 'uuid'));
    }

    public function testEncodeEmptyStringReturnsNull()
    {
        $this->assertNull(Binary::encode('', 'uuid'));
    }

    public function testDecodeEmptyStringReturnsNull()
    {
        $this->assertNull(Binary::decode('', 'uuid'));
    }

    public function testEncodeWithInvalidFormatThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format [invalid] is invalid.');

        Binary::encode('value', 'invalid');
    }

    public function testDecodeWithInvalidFormatThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format [invalid] is invalid.');

        Binary::decode('value', 'invalid');
    }

    public function testEncodeUuidFromBinaryInput()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $bytes = Uuid::fromString($uuid)->getBytes();

        // Encoding binary should return the same bytes
        $this->assertSame($bytes, Binary::encode($bytes, 'uuid'));
    }

    public function testDecodeUuidFromStringInput()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        // Decoding string UUID should return the same string
        $this->assertSame($uuid, Binary::decode($uuid, 'uuid'));
    }

    public function testCustomFormatOverridesDefault()
    {
        Binary::registerFormat('uuid', fn ($v) => 'custom-encode', fn ($v) => 'custom-decode');

        $this->assertSame('custom-encode', Binary::encode('test', 'uuid'));
        $this->assertSame('custom-decode', Binary::decode('test', 'uuid'));
    }
}
