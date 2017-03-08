<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Traits\Whenable;

class SupportWhenableTest extends TestCase
{
    use Whenable;

    public function testNotApplyGivenCallbackWhenFalse()
    {
        $result = $this->when(false, function ($self) {
            return 'Whenable';
        });

        $this->assertEquals($result, $this);
    }

    public function testNotApplyCallbackWhenTrue()
    {
        $result = $this->when(true, function ($self) {
            return 'Whenable';
        });

        $this->assertEquals($result, 'Whenable');
    }

    public function testApplyDefaultCallbackWhenFalse()
    {
        $method = __METHOD__;

        $result = $this->when(false, function ($self) {
            return 'Whenable';
        }, function ($self) use ($method) {
            return $self->callDefault($method);
        });

        $this->assertEquals($result, $method . '!');
    }

    public function callDefault($message)
    {
        return $message . '!';
    }
}
