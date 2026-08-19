<?php

namespace Illuminate\Tests\Pagination;

use Illuminate\Pagination\Cursor;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CursorTest extends TestCase
{
    public function testCanEncodeAndDecodeSuccessfully()
    {
        $cursor = new Cursor([
            'id' => 422,
            'created_at' => Carbon::now()->toDateTimeString(),
        ], true);

        $this->assertEquals($cursor, Cursor::fromEncoded($cursor->encode()));
    }

    public function testFromEncodedReturnsNullForNonStringInput()
    {
        $this->assertNull(Cursor::fromEncoded(null));
        $this->assertNull(Cursor::fromEncoded(123));
    }

    public function testFromEncodedReturnsNullForInvalidJson()
    {
        $this->assertNull(Cursor::fromEncoded(base64_encode('not-json')));
    }

    public function testFromEncodedReturnsNullWhenDecodedPayloadIsNotAnArray()
    {
        $this->assertNull(Cursor::fromEncoded(base64_encode(json_encode('scalar'))));
        $this->assertNull(Cursor::fromEncoded(base64_encode(json_encode(null))));
    }

    public function testFromEncodedReturnsNullWhenPointsToNextItemsKeyIsMissing()
    {
        $payload = base64_encode(json_encode(['id' => 422]));

        $this->assertNull(Cursor::fromEncoded($payload));
    }

    public function testCanGetParams()
    {
        $cursor = new Cursor([
            'id' => 422,
            'created_at' => ($now = Carbon::now()->toDateTimeString()),
        ], true);

        $this->assertEquals([$now, 422], $cursor->parameters(['created_at', 'id']));
    }

    public function testCanGetParam()
    {
        $cursor = new Cursor([
            'id' => 422,
            'created_at' => ($now = Carbon::now()->toDateTimeString()),
        ], true);

        $this->assertEquals($now, $cursor->parameter('created_at'));
    }
}
