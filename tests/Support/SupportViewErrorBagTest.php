<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\TestCase;

class SupportViewErrorBagTest extends TestCase
{
    public function testHasBagTrue()
    {
        $viewErrorBag = new ViewErrorBag;
        $viewErrorBag->put('default', new MessageBag(['msg1', 'msg2']));
        $this->assertTrue($viewErrorBag->hasBag());
    }

    public function testHasBagFalse()
    {
        $viewErrorBag = new ViewErrorBag;
        $this->assertFalse($viewErrorBag->hasBag());
    }

    public function testGet()
    {
        $messageBag = new MessageBag;
        $viewErrorBag = new ViewErrorBag;
        $viewErrorBag = $viewErrorBag->put('default', $messageBag);
        $this->assertEquals($messageBag, $viewErrorBag->getBag('default'));
    }

    public function testGetBagWithNew()
    {
        $viewErrorBag = new ViewErrorBag;
        $this->assertInstanceOf(MessageBag::class, $viewErrorBag->getBag('default'));
    }

    public function testGetBags()
    {
        $messageBag1 = new MessageBag;
        $messageBag2 = new MessageBag;
        $viewErrorBag = new ViewErrorBag;
        $viewErrorBag->put('default', $messageBag1);
        $viewErrorBag->put('default2', $messageBag2);
        $this->assertEquals([
            'default' => $messageBag1,
            'default2' => $messageBag2,
        ], $viewErrorBag->getBags());
    }

    public function testPut()
    {
        $messageBag = new MessageBag;
        $viewErrorBag = new ViewErrorBag;
        $viewErrorBag = $viewErrorBag->put('default', $messageBag);
        $this->assertEquals(['default' => $messageBag], $viewErrorBag->getBags());
    }

    public function testAnyTrue()
    {
        $viewErrorBag = new ViewErrorBag;
        $viewErrorBag->put('default', new MessageBag(['message']));
        $this->assertTrue($viewErrorBag->any());
    }

    public function testAnyFalse()
    {
        $viewErrorBag = new ViewErrorBag;
        $viewErrorBag->put('default', new MessageBag);
        $this->assertFalse($viewErrorBag->any());
    }

    public function testAnyFalseWithEmptyErrorBag()
    {
        $viewErrorBag = new ViewErrorBag;
        $this->assertFalse($viewErrorBag->any());
    }

    public function testCount()
    {
        $viewErrorBag = new ViewErrorBag;
        $viewErrorBag->put('default', new MessageBag(['message', 'second']));
        $this->assertCount(2, $viewErrorBag);
    }

    public function testCountWithNoMessagesInMessageBag()
    {
        $viewErrorBag = new ViewErrorBag;
        $viewErrorBag->put('default', new MessageBag);
        $this->assertCount(0, $viewErrorBag);
    }

    public function testCountWithNoMessageBags()
    {
        $viewErrorBag = new ViewErrorBag;
        $this->assertCount(0, $viewErrorBag);
    }

    public function testDynamicCallToDefaultMessageBag()
    {
        $viewErrorBag = new ViewErrorBag;
        $viewErrorBag->put('default', new MessageBag(['message', 'second']));
        $this->assertEquals(['message', 'second'], $viewErrorBag->all());
    }

    public function testDynamicallyGetBag()
    {
        $messageBag = new MessageBag;
        $viewErrorBag = new ViewErrorBag;
        $viewErrorBag = $viewErrorBag->put('default', $messageBag);
        $this->assertEquals($messageBag, $viewErrorBag->default);
    }

    public function testDynamicallyPutBag()
    {
        $messageBag = new MessageBag;
        $viewErrorBag = new ViewErrorBag;
        $viewErrorBag->default2 = $messageBag;
        $this->assertEquals(['default2' => $messageBag], $viewErrorBag->getBags());
    }

    public function testToString()
    {
        $viewErrorBag = new ViewErrorBag;
        $viewErrorBag = $viewErrorBag->put('default', new MessageBag(['message' => 'content']));
        $this->assertSame('{"message":["content"]}', (string) $viewErrorBag);
    }
}
