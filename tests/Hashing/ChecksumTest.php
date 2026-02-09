<?php

namespace Hashing;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Hashing\Checksum;
use JsonSerializable;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplFileInfo;
use Stringable;

class ChecksumTest extends TestCase
{
    protected Checksum $checksum;

    protected function setUp(): void
    {
        $this->checksum = new Checksum();
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public static function providesDataWithChecksums(): array
    {
        $string = '["test-string"]';

        $hashes = [
            'crc32' => hash('crc32b', $string),
            'md5' => hash('md5', $string),
            'sha256' => hash('sha256', $string),
            'xxh3' => hash('xxh3', $string),
            'xxh128' => hash('xxh128', $string),
        ];

        return [
            [
                function () {
                    $resource = fopen('php://memory', 'r+');
                    fwrite($resource, '["test-string"]');
                    rewind($resource);
                    return $resource;
                },
                $hashes,
            ],
            [
                function () {
                    $file = m::mock(SplFileInfo::class);
                    $file->expects('getRealPath')->andReturn(
                        'data://text/plain;base64,'.base64_encode('["test-string"]'),
                    )->atLeast()->once();
                    return $file;
                },
                $hashes,
            ],
            [
                ['test-string'],
                $hashes,
            ],
            [
                function () {
                    $file = m::mock(JsonSerializable::class);
                    $file->expects('jsonSerialize')->andReturn(['test-string'])->atLeast()->once();
                    return $file;
                },
                $hashes,
            ],
            [
                function () {
                    $arrayable = m::mock(Arrayable::class);
                    $arrayable->expects('toArray')->andReturn(['test-string'])->atLeast()->once();
                    return $arrayable;
                },
                $hashes,
            ],
            [
                function () {
                    $jsonable = m::mock(Jsonable::class);
                    $jsonable->expects('toJson')->andReturn('["test-string"]')->atLeast()->once();
                    return $jsonable;
                },
                $hashes,
            ],
            [
                new class implements Stringable
                {
                    public function __toString()
                    {
                        return '["test-string"]';
                    }
                },
                $hashes,
            ],
            [
                $string,
                $hashes,
            ],
        ];
    }

    #[DataProvider('providesDataWithChecksums')]
    public function testCrc32($data, $checksums)
    {
        if ($data instanceof Closure) {
            $data = $data();
        }

        try {
            $this->assertEquals($checksums['crc32'], $this->checksum->crc32($data)->hash());
        } finally {
            if (is_resource($data)) {
                fclose($data);
            }
        }
    }

    #[DataProvider('providesDataWithChecksums')]
    public function testMd5($data, $checksums)
    {
        if ($data instanceof Closure) {
            $data = $data();
        }

        try {
            $this->assertEquals($checksums['md5'], $this->checksum->md5($data)->hash());
        } finally {
            if (is_resource($data)) {
                fclose($data);
            }
        }
    }

    #[DataProvider('providesDataWithChecksums')]
    public function testSha256($data, $checksums)
    {
        if ($data instanceof Closure) {
            $data = $data();
        }

        try {
            $this->assertEquals($checksums['sha256'], $this->checksum->sha256($data)->hash());
        } finally {
            if (is_resource($data)) {
                fclose($data);
            }
        }
    }

    #[DataProvider('providesDataWithChecksums')]
    public function testXxh3($data, $checksums)
    {
        if ($data instanceof Closure) {
            $data = $data();
        }

        try {
            $this->assertEquals($checksums['xxh3'], $this->checksum->xxh3($data)->hash());
        } finally {
            if (is_resource($data)) {
                fclose($data);
            }
        }
    }

    #[DataProvider('providesDataWithChecksums')]
    public function testXxh128($data, $checksums)
    {
        if ($data instanceof Closure) {
            $data = $data();
        }

        try {
            $this->assertEquals($checksums['xxh128'], $this->checksum->xxh128($data)->hash());
        } finally {
            if (is_resource($data)) {
                fclose($data);
            }
        }
    }

    #[DataProvider('providesDataWithChecksums')]
    public function testIs($data)
    {
        $comparable = $data;

        if ($data instanceof Closure) {
            $data = $data();
            $comparable = $comparable();
        }

        try {
            $this->assertTrue($this->checksum->crc32($data)->is((new Checksum())->crc32($comparable)->hash()));
            $this->assertFalse($this->checksum->crc32($data)->isNot((new Checksum())->crc32($comparable)->hash()));

            $this->assertTrue($this->checksum->crc32($data)->isNot('invalid-hash'));
            $this->assertFalse($this->checksum->crc32($data)->is('invalid-hash'));
        } finally {
            if (is_resource($data)) {
                fclose($data);
                fclose($comparable);
            }
        }
    }

    #[DataProvider('providesDataWithChecksums')]
    public function testIsSameHashOf($data)
    {
        $comparable = $data;

        if ($data instanceof Closure) {
            $data = $data();
            $comparable = $comparable();
        }

        try {
            $this->assertTrue($this->checksum->crc32($data)->isSameHashOf($comparable));
            $this->assertFalse($this->checksum->crc32($data)->isNotSameHashOf($comparable));

            $this->assertTrue($this->checksum->crc32($data)->isNotSameHashOf('invalid-hash'));
            $this->assertFalse($this->checksum->crc32($data)->isSameHashOf('invalid-hash'));
        } finally {
            if (is_resource($data)) {
                fclose($data);
                fclose($comparable);
            }
        }
    }

    public function testThrowsIfNoDataWasChecksum()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No checksum was calculated.');

        $this->checksum->hash();
    }
}
