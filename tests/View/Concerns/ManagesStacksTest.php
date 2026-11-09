<?php

namespace Illuminate\Tests\View\Concerns;

use Illuminate\View\Concerns\ManagesStacks;
use PHPUnit\Framework\TestCase;

class ManagesStacksTest extends TestCase
{
    public function testStackIsEmpty()
    {
        $this->assertTrue((new FakeViewFactory)->isStackEmpty('my-stack'));
    }

    public function testStackIsNotEmptyWithPushedContent()
    {
        $object = new FakeViewFactory;
        $object->startPush('my-stack', 'some pushed content');

        $this->assertFalse($object->isStackEmpty('my-stack'));
    }

    public function testStackIsNotEmptyWithPrependedContent()
    {
        $object = new FakeViewFactory;
        $object->startPrepend('my-stack', 'some prepended content');

        $this->assertFalse($object->isStackEmpty('my-stack'));
    }

    public function testYieldPushContentStaysImmediate()
    {
        $object = new FakeViewFactory;
        $object->startPush('scripts', 'hello');

        $this->assertSame('hello', $object->yieldPushContent('scripts'));
    }

    public function testDeferredStackAppendsPushesThatHappenAfterIt()
    {
        $object = new FakeViewFactory;
        $object->startPush('scripts', 'before');

        $token = $object->yieldStackPlaceholder('scripts');

        $object->startPush('scripts', 'after');

        $this->assertSame('beforeafter', $object->substituteDeferredStacks($token));
        $this->assertSame('beforeafter', $object->yieldPushContent('scripts'));
    }

    public function testEarlierStackKeepsItsSnapshot()
    {
        $object = new FakeViewFactory;
        $object->startPush('scripts', 'one');

        $first = $object->yieldStackPlaceholder('scripts');

        $object->startPush('scripts', 'two');

        $second = $object->yieldStackPlaceholder('scripts');

        $object->startPush('scripts', 'three');

        $rendered = $object->substituteDeferredStacks($first.'|'.$second);

        $this->assertSame('one|onetwothree', $rendered);
    }

    public function testDeferredStackPrependsAfterThePlaceholder()
    {
        $object = new FakeViewFactory;
        $object->startPush('scripts', 'body');

        $token = $object->yieldStackPlaceholder('scripts');

        $object->startPrepend('scripts', 'head');

        $this->assertSame('headbody', $object->substituteDeferredStacks($token));
    }

    public function testEmptyStackDefaultIsReplacedWhenContentArrivesLater()
    {
        $object = new FakeViewFactory;

        $token = $object->yieldStackPlaceholder('scripts', 'fallback');

        $this->assertSame('fallback', $object->substituteDeferredStacks($token));

        $object->startPush('scripts', 'later');

        $this->assertSame('later', $object->substituteDeferredStacks($token));
    }

    public function testDeferredStackDoesNotMoveLatePushesAheadOfEarlierContent()
    {
        $object = new class extends FakeViewFactory
        {
            public function setRenderCount(int $count): void
            {
                $this->renderCount = $count;
            }
        };

        $object->setRenderCount(2);
        $object->startPush('scripts', 'chat-view');
        $object->setRenderCount(1);
        $object->startPush('scripts', 'from-view');

        $token = $object->yieldStackPlaceholder('scripts');

        $object->setRenderCount(2);
        $object->startPush('scripts', 'from-layout');

        $this->assertSame(
            'chat-viewfrom-viewfrom-layout',
            $object->substituteDeferredStacks($token)
        );
    }

    public function testDeferredStacksAreClearedWithTheFactoryState()
    {
        $object = new FakeViewFactory;
        $token = $object->yieldStackPlaceholder('scripts', 'fallback');

        $object->flushStacks();

        $this->assertSame($token, $object->substituteDeferredStacks($token));
    }
}

class FakeViewFactory
{
    use ManagesStacks;

    protected int $renderCount = 0;
}
