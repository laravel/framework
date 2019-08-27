<?php

namespace Illuminate\Tests\Notifications;

use PHPUnit\Framework\TestCase;
use Illuminate\Notifications\Action;

class NotificationActionTest extends TestCase
{
    public function testActionIsCreatedProperly()
    {
        $action = new Action('Text', 'url');

        $this->assertSame('Text', $action->text);
        $this->assertSame('url', $action->url);
    }
}
