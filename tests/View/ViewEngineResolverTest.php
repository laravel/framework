<?php

namespace Illuminate\Tests\View;

use stdClass;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ViewEngineResolverTest extends TestCase
{
    public function testResolversMayBeResolved()
    {
        $resolver = new \Illuminate\View\Engines\EngineResolver;
        $resolver->register('foo', function () {
            return new StdClass;
        });
        $result = $resolver->resolve('foo');

        $this->assertEquals(spl_object_hash($result), spl_object_hash($resolver->resolve('foo')));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testResolverThrowsExceptionOnUnknownEngine()
    {
        $resolver = new \Illuminate\View\Engines\EngineResolver;
        $resolver->resolve('foo');
    }
}
