<?php

namespace Illuminate\Tests\Container;

use Illuminate\Container\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    public function testUnwrapIfClosure()
    {
        $this->assertSame('foo', Util::unwrapIfClosure('foo'));
        $this->assertSame('foo', Util::unwrapIfClosure(function () {
            return 'foo';
        }));
    }
}
