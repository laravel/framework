<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Casts\AsBinary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\BinaryCodec;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Uid\Ulid;

class DatabaseEloquentAsBinaryCastTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflection = new \ReflectionClass(BinaryCodec::class);
        $property = $reflection->getProperty('customCodecs');
        $property->setValue(null, []);

        parent::tearDown();
    }

    public function testCastThrowsWhenFormatMissing()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The binary codec format is required.');

        $model = new AsBinaryTestModel;
        $model->setRawAttributes(['no_format' => 'value']);
        $model->no_format;
    }

    public function testCastThrowsOnInvalidFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported binary codec format [invalid]. Allowed formats are: uuid, ulid.');

        $model = new AsBinaryTestModel;
        $model->setRawAttributes(['invalid_format' => 'value']);
        $model->invalid_format;
    }

    public function testGetDecodesUuidFromBinary()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $model = new AsBinaryTestModel;
        $model->setRawAttributes(['uuid' => Uuid::fromString($uuid)->getBytes()]);

        $this->assertSame($uuid, $model->uuid);
    }

    public function testSetEncodesUuidToBinary()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $model = new AsBinaryTestModel;
        $model->uuid = $uuid;

        $this->assertSame(Uuid::fromString($uuid)->getBytes(), $model->getAttributes()['uuid']);
    }

    public function testGetDecodesUlidFromBinary()
    {
        $ulid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $model = new AsBinaryTestModel;
        $model->setRawAttributes(['ulid' => Ulid::fromString($ulid)->toBinary()]);

        $this->assertSame($ulid, $model->ulid);
    }

    public function testSetEncodesUlidToBinary()
    {
        $ulid = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        $model = new AsBinaryTestModel;
        $model->ulid = $ulid;

        $this->assertSame(Ulid::fromString($ulid)->toBinary(), $model->getAttributes()['ulid']);
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

    public function testUuidHelperMethod()
    {
        $this->assertSame(AsBinary::class.':uuid', AsBinary::uuid());
    }

    public function testUlidHelperMethod()
    {
        $this->assertSame(AsBinary::class.':ulid', AsBinary::ulid());
    }

    public function testOfHelperMethod()
    {
        $this->assertSame(AsBinary::class.':custom', AsBinary::of('custom'));
    }
}

class AsBinaryTestModel extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'uuid' => AsBinary::class.':uuid',
            'ulid' => AsBinary::class.':ulid',
            'no_format' => AsBinary::class,
            'invalid_format' => AsBinary::class.':invalid',
        ];
    }
}
