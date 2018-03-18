<?php

namespace Illuminate\Tests\Notifications;

use PHPUnit\Framework\TestCase;
use Illuminate\Notifications\Action;

class NotificationActionTest extends TestCase
{
    public function testActionIsCreatedProperly(): void
    {
        $action = new Action('Text', 'url');

        $this->assertEquals('Text', $action->text);
        $this->assertEquals('url', $action->url);
    }
}
