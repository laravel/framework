<?php

namespace Illuminate\Tests\View;

use stdClass;
use PHPUnit\Framework\TestCase;

class ViewEngineResolverTest extends TestCase
{
    public function testResolversMayBeResolved()
    {
        $resolver = new \Illuminate\View\Engines\EngineResolver;
        $resolver->register('foo', function () {
            return new stdClass;
        });
        $result = $resolver->resolve('foo');

        $this->assertEquals(spl_object_hash($result), spl_object_hash($resolver->resolve('foo')));
    }

    public function testResolverThrowsExceptionOnUnknownEngine()
    {
        $this->expectException(\InvalidArgumentException::class);

        $resolver = new \Illuminate\View\Engines\EngineResolver;
        $resolver->resolve('foo');
    }
}
