<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Contracts\Mail\Mailable;
use PHPUnit\Framework\Assert as PHPUnit;

class TestMailable
{
    /**
     * @var Mailable
     */
    private $mailables;
    /**
     * @var int
     */
    private $times;

    public function __construct($mailables, $times = 1)
    {
        $this->mailables = $mailables;
        $this->times = $times;
    }

    /**
     * Assert if the given recipient is set on the mailable.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return \Illuminate\Foundation\Testing\TestMailable
     */
    public function hasTo($address, $name = null)
    {
        return $this->assertFilter(function ($mailable) use ($address, $name) {
            return $mailable->hasTo($address, $name);
        }, "was not sent to {$address} {$name}");
    }

    /**
     * Assert if the given recipient is set on the mailable.
     *
     * @param  object|array|string  $address
     * @param  string|null  $name
     * @return \Illuminate\Foundation\Testing\TestMailable
     */
    public function hasCc($address, $name = null)
    {
        return $this->assertFilter(function ($mailable) use ($address, $name) {
            return $mailable->hasCc($address, $name);
        }, "was not sent with CC to {$address} {$name}");
    }

    /**
     * Assert if the given property is set on the mailable
     */
    public function has($property)
    {
        $result = $this->assertFilter(function ($mailable) use ($property) {
            return isset($mailable->$property);
        }, "does not contain the property {$property}");

        return new TestData($result->mailables->first()->$property, $result);
        //TODO: Need to create another object to assert multiple data, maybe TestMultipleData
    }

    protected function assertFilter($callback, $message)
    {
        $result = new static($this->mailables->filter($callback));

        if ($result->mailables->count() < $this->times) {
            PHPUnit::fail($this->failureDescription($message));
        }

        return $result;
    }

    protected function failureDescription($message)
    {
        $result = "The mailable {$this->name()} $message ";

        if ($this->times > 1) {
            $result .= " {$this->times} times";
        }

        return $result;
    }

    protected function name()
    {
        return class_basename($this->mailables->first());
    }
}
