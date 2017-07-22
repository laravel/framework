<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

class SupportViewErrorBagTest extends TestCase
{
    public function testAnyBagHasIndicatesExistence()
    {
        $errors = new ViewErrorBag;

        $fooBag = new MessageBag;
        $fooBag->add('foo', 'bar');
        $barBag = new MessageBag;
        $barBag->add('baz', 'qux');

        $errors->put('foo', $fooBag);
        $errors->put('bar', $barBag);

        $this->assertTrue($errors->anyBagHas('foo'));
        $this->assertTrue($errors->anyBagHas('baz'));
        $this->assertTrue($errors->anyBagHas('foo', 'baz'));
        $this->assertTrue($errors->anyBagHas(['foo', 'baz']));
        $this->assertFalse($errors->anyBagHas('bag'));
        $this->assertFalse($errors->anyBagHas(['bag']));
        $this->assertFalse($errors->anyBagHas('bag', 'foo'));
    }

    public function testAnyBagHasAnyIndicatesExistence()
    {
        $errors = new ViewErrorBag;

        $fooBag = new MessageBag;
        $fooBag->add('foo', 'bar');
        $barBag = new MessageBag;
        $barBag->add('baz', 'qux');

        $errors->put('foo', $fooBag);
        $errors->put('bar', $barBag);

        $this->assertTrue($errors->anyBagHasAny('foo'));
        $this->assertTrue($errors->anyBagHasAny('baz'));
        $this->assertTrue($errors->anyBagHasAny('foo', 'baz'));
        $this->assertTrue($errors->anyBagHasAny(['foo', 'baz']));
        $this->assertFalse($errors->anyBagHasAny('bag'));
        $this->assertFalse($errors->anyBagHasAny(['bag']));
        $this->assertTrue($errors->anyBagHasAny('bag', 'foo'));
        $this->assertTrue($errors->anyBagHasAny(['foo', 'baz', 'biz']));
        $this->assertTrue($errors->anyBagHasAny('foo', 'baz', 'biz'));
    }
}
