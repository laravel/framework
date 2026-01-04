<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Casts\AsBinary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\BinaryCodec;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class DatabaseEloquentAsBinaryCastTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(BinaryCodec::class);
        $property = $reflection->getProperty('customFormats');
        $property->setAccessible(true);
        $property->setValue(null, []);

        parent::tearDown();
    }

    public function testCastRequiresFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The binary format is required.');

        $model = new AsBinaryTestModel;
        $model->setRawAttributes(['no_format' => 'value']);
        $model->no_format;
    }

    public function testCastThrowsOnInvalidFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided format [invalid] is invalid.');

        $model = new AsBinaryTestModel;
        $model->setRawAttributes(['invalid_format' => 'value']);
        $model->invalid_format;
    }

    public function testGetDecodesUuidFromBinary()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $bytes = Uuid::fromString($uuid)->getBytes();

        $model = new AsBinaryTestModel;
        $model->setRawAttributes(['uuid' => $bytes]);

        $this->assertSame($uuid, $model->uuid);
    }

    public function testSetEncodesUuidToBinary()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $expectedBytes = Uuid::fromString($uuid)->getBytes();

        $model = new AsBinaryTestModel;
        $model->uuid = $uuid;

        $this->assertSame($expectedBytes, $model->getAttributes()['uuid']);
    }

    public function testGetReturnsNullForNullValue()
    {
        $model = new AsBinaryTestModel;
        $model->setRawAttributes(['uuid' => null]);

        $this->assertNull($model->uuid);
    }

    public function testSetEncodesNullToNull()
    {
        $model = new AsBinaryTestModel;
        $model->uuid = null;

        $this->assertNull($model->getAttributes()['uuid']);
    }
}

class AsBinaryTestModel extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'uuid' => AsBinary::class.':uuid',
            'no_format' => AsBinary::class,
            'invalid_format' => AsBinary::class.':invalid',
        ];
    }
}
