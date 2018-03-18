<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

class SupportViewErrorBagTest extends TestCase
{
    public function testHasBagTrue(): void
    {
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag->put('default', new MessageBag(['msg1', 'msg2']));
        $this->assertTrue($viewErrorBag->hasBag());
    }

    public function testHasBagFalse(): void
    {
        $viewErrorBag = new ViewErrorBag();
        $this->assertFalse($viewErrorBag->hasBag());
    }

    public function testGet(): void
    {
        $messageBag = new MessageBag();
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag = $viewErrorBag->put('default', $messageBag);
        $this->assertEquals($messageBag, $viewErrorBag->getBag('default'));
    }

    public function testGetBagWithNew(): void
    {
        $viewErrorBag = new ViewErrorBag();
        $this->assertInstanceOf(MessageBag::class, $viewErrorBag->getBag('default'));
    }

    public function testGetBags(): void
    {
        $messageBag1 = new MessageBag();
        $messageBag2 = new MessageBag();
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag->put('default', $messageBag1);
        $viewErrorBag->put('default2', $messageBag2);
        $this->assertEquals([
            'default' => $messageBag1,
            'default2' => $messageBag2,
        ], $viewErrorBag->getBags());
    }

    public function testPut(): void
    {
        $messageBag = new MessageBag();
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag = $viewErrorBag->put('default', $messageBag);
        $this->assertEquals(['default' => $messageBag], $viewErrorBag->getBags());
    }

    public function testAnyTrue(): void
    {
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag->put('default', new MessageBag(['message']));
        $this->assertTrue($viewErrorBag->any());
    }

    public function testAnyFalse(): void
    {
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag->put('default', new MessageBag());
        $this->assertFalse($viewErrorBag->any());
    }

    public function testAnyFalseWithEmptyErrorBag(): void
    {
        $viewErrorBag = new ViewErrorBag();
        $this->assertFalse($viewErrorBag->any());
    }

    public function testCount(): void
    {
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag->put('default', new MessageBag(['message', 'second']));
        $this->assertEquals(2, $viewErrorBag->count());
    }

    public function testCountWithNoMessagesInMessageBag(): void
    {
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag->put('default', new MessageBag());
        $this->assertEquals(0, $viewErrorBag->count());
    }

    public function testCountWithNoMessageBags(): void
    {
        $viewErrorBag = new ViewErrorBag();
        $this->assertEquals(0, $viewErrorBag->count());
    }

    public function testDynamicCallToDefaultMessageBag(): void
    {
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag->put('default', new MessageBag(['message', 'second']));
        $this->assertEquals(['message', 'second'], $viewErrorBag->all());
    }

    public function testDynamicallyGetBag(): void
    {
        $messageBag = new MessageBag();
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag = $viewErrorBag->put('default', $messageBag);
        $this->assertEquals($messageBag, $viewErrorBag->default);
    }

    public function testDynamicallyPutBag(): void
    {
        $messageBag = new MessageBag();
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag->default2 = $messageBag;
        $this->assertEquals(['default2' => $messageBag], $viewErrorBag->getBags());
    }

    public function testToString(): void
    {
        $viewErrorBag = new ViewErrorBag();
        $viewErrorBag = $viewErrorBag->put('default', new MessageBag(['message' => 'content']));
        $this->assertEquals('{"message":["content"]}', (string) $viewErrorBag);
    }
}
