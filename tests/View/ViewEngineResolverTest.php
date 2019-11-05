<?php

namespace Illuminate\Tests\View;

use Illuminate\View\Engines\EngineResolver;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ViewEngineResolverTest extends TestCase
{
    public function testResolversMayBeResolved()
    {
        $resolver = new EngineResolver;
        $resolver->register('foo', function () {
            return new stdClass;
        });
        $result = $resolver->resolve('foo');

        $this->assertEquals(spl_object_hash($result), spl_object_hash($resolver->resolve('foo')));
    }

    public function testResolverThrowsExceptionOnUnknownEngine()
    {
        $this->expectException(InvalidArgumentException::class);

        $resolver = new EngineResolver;
        $resolver->resolve('foo');
    }
}
