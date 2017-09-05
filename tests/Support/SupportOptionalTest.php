<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Optional;
use PHPUnit\Framework\TestCase;

class SupportOptionalTest extends TestCase
{
    public function testAccessValueAttribue()
    {
        $target = new \StdClass;
        $target->randomValue = str_random(24);

        $optional = new Optional($target);

        $this->assertSame($target->randomValue, $optional->randomValue);
    }

    public function testReturnDefaultWhenValueIsNull()
    {
        $randomValue = str_random(24);

        $optional = new Optional(null);

        $this->assertSame($randomValue, $optional->get('attribute', $randomValue));
    }

    public function testReturnDefaultAttributeIsNotFound()
    {
        $target = new \StdClass;

        $randomValue = str_random(24);

        $optional = new Optional($target);

        $this->assertSame($randomValue, $optional->get('attribute', $randomValue));
    }

    public function testExecuteMethodOnValue()
    {
        $target = new Target();
        
        $optional = new Optional($target);

        $randomValue = str_random(24);

        $this->assertSame($randomValue, $optional->someMethod($randomValue));
    }
}

class Target 
{
    function someMethod($attribute)
    {
        return $attribute;
    }
}
