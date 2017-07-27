<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

class SupportMessageBagsTest extends TestCase
{
    public function testBagsHaveIndicatesExistence()
    {
        $errors = new ViewErrorBag;
        $this->assertFalse($errors->bags()->have('foo'));

        $fooBag = new MessageBag;
        $fooBag->add('foo', 'bar');
        $barBag = new MessageBag;
        $barBag->add('baz', 'qux');

        $errors->put('foo', $fooBag);
        $errors->put('bar', $barBag);

        $this->assertcount(2, $errors->bags());
        $this->assertTrue($errors->bags()->have('foo'));
        $this->assertTrue($errors->bags()->have('baz'));
        $this->assertTrue($errors->bags()->have('foo', 'baz'));
        $this->assertTrue($errors->bags()->have(['foo', 'baz']));
        $this->assertFalse($errors->bags()->have('bag'));
        $this->assertFalse($errors->bags()->have(['bag']));
        $this->assertFalse($errors->bags()->have('bag', 'foo'));
    }

    public function testBagsHaveAnyIndicatesExistence()
    {
        $errors = new ViewErrorBag;
        $this->assertFalse($errors->bags()->haveAny('foo'));

        $fooBag = new MessageBag;
        $fooBag->add('foo', 'bar');
        $barBag = new MessageBag;
        $barBag->add('baz', 'qux');

        $errors->put('foo', $fooBag);
        $errors->put('bar', $barBag);

        $this->assertTrue($errors->bags()->haveAny('foo'));
        $this->assertTrue($errors->bags()->haveAny('baz'));
        $this->assertTrue($errors->bags()->haveAny('foo', 'baz'));
        $this->assertTrue($errors->bags()->haveAny(['foo', 'baz']));
        $this->assertFalse($errors->bags()->haveAny('bag'));
        $this->assertFalse($errors->bags()->haveAny(['bag']));
        $this->assertTrue($errors->bags()->haveAny('bag', 'foo'));
        $this->assertTrue($errors->bags()->haveAny(['foo', 'baz', 'biz']));
        $this->assertTrue($errors->bags()->haveAny('foo', 'baz', 'biz'));
    }
}
